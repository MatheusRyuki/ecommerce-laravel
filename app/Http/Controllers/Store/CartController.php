<?php

namespace App\Http\Controllers\Store;

use App\Cart\CartException;
use App\Cart\CartItemNotFoundException;
use App\Cart\SessionCart;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCartItemRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CartController extends Controller
{
    public function __construct(private SessionCart $cart) {}

    public function index(): View
    {
        $lines = $this->cart->lines();
        $productTotal = $this->cart->productTotal($lines);

        return view('store.cart', [
            'lines' => $lines,
            'productTotal' => $productTotal,
            'formattedProductTotal' => $productTotal === null ? null : Money::formatBrl($productTotal),
        ]);
    }

    public function storeItem(StoreCartItemRequest $request): RedirectResponse
    {
        $previousItems = $this->cart->items();

        try {
            $this->cart->add(
                (int) $request->validated('product_id'),
                (string) $request->validated('color'),
                (int) $request->validated('quantity'),
            );
        } catch (CartException $exception) {
            $request->session()->put(SessionCart::SESSION_KEY, $previousItems);

            return back()
                ->withInput()
                ->withErrors(['cart' => $exception->getMessage()]);
        }

        return redirect()
            ->route('cart')
            ->with('status', __('The product was added to the cart.'));
    }

    public function updateItem(UpdateCartItemRequest $request, string $item): RedirectResponse
    {
        $previousItems = $this->cart->items();

        try {
            $this->cart->updateQuantity($item, (int) $request->validated('quantity'));
        } catch (CartItemNotFoundException) {
            throw new NotFoundHttpException;
        } catch (CartException $exception) {
            $request->session()->put(SessionCart::SESSION_KEY, $previousItems);

            return back()
                ->withInput()
                ->withErrors(['cart_items.'.$item => $exception->getMessage()]);
        }

        return redirect()
            ->route('cart')
            ->with('status', __('The cart was updated.'));
    }

    public function destroyItem(string $item): RedirectResponse
    {
        try {
            $this->cart->remove($item);
        } catch (CartItemNotFoundException) {
            throw new NotFoundHttpException;
        }

        return redirect()
            ->route('cart')
            ->with('status', __('The item was removed from the cart.'));
    }
}
