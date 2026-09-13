<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-blue-600 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between px-4 py-3 bg-gray-50 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">{{ __('Products') }}</h3>
                    <a id="create-product-link" href="{{ route('admin.products.create') }}" class="relative z-10 inline-flex items-center justify-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        {{ __('Create Product') }}
                    </a>
                </div>

                <div class="p-4 sm:p-6">
                    @php
                        $statusKey = session('status');
                        $statusMessage = match ($statusKey) {
                            'product-created' => __('Product created successfully.'),
                            'product-updated' => __('Product updated successfully.'),
                            'product-deleted' => __('Product deleted successfully.'),
                            'product-deleted-with-pending-cleanup' => __('The product was deleted, but some image files could not be removed and need cleanup.'),
                            default => $statusKey,
                        };
                        $statusTone = $statusKey === 'product-deleted-with-pending-cleanup' ? 'warning' : 'success';
                    @endphp
                    <x-admin-alert class="mb-4" :status="$statusMessage" :tone="$statusTone" :dismissible="$statusKey !== 'product-deleted-with-pending-cleanup'" />

                    @if ($products->isEmpty())
                        <div class="text-center py-10 space-y-4">
                            <p class="text-sm text-gray-600">{{ __('No products have been registered yet.') }}</p>
                            <a href="{{ route('admin.products.create') }}" class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                {{ __('Create Product') }}
                            </a>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-3 py-3 text-left font-medium text-gray-600">{{ __('Cover') }}</th>
                                        <th scope="col" class="px-3 py-3 text-left font-medium text-gray-600">{{ __('Name') }}</th>
                                        <th scope="col" class="px-3 py-3 text-left font-medium text-gray-600">{{ __('SKU') }}</th>
                                        <th scope="col" class="px-3 py-3 text-left font-medium text-gray-600">{{ __('Price (BRL)') }}</th>
                                        <th scope="col" class="px-3 py-3 text-left font-medium text-gray-600">{{ __('Qty') }}</th>
                                        <th scope="col" class="px-3 py-3 text-left font-medium text-gray-600">{{ __('Colors') }}</th>
                                        <th scope="col" class="px-3 py-3 text-left font-medium text-gray-600">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    @foreach ($products as $product)
                                        @php
                                            $coverUrl = $product->coverUrl();
                                        @endphp
                                        <tr>
                                            <td class="px-3 py-3">
                                                @if ($coverUrl)
                                                    <img src="{{ $coverUrl }}" alt="{{ $product->name }}" class="h-12 w-12 rounded object-contain border border-gray-200 bg-gray-50">
                                                @else
                                                    <span class="inline-flex h-12 w-12 items-center justify-center rounded border border-dashed border-gray-300 bg-gray-50 text-[10px] text-gray-400">{{ __('No cover') }}</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-3 text-gray-800 whitespace-nowrap">{{ $product->name }}</td>
                                            <td class="px-3 py-3 text-gray-700 font-mono whitespace-nowrap">{{ $product->sku }}</td>
                                            <td class="px-3 py-3 text-gray-700 whitespace-nowrap">{{ $product->formattedPrice() }}</td>
                                            <td class="px-3 py-3 text-gray-700 whitespace-nowrap">{{ $product->qty }}</td>
                                            <td class="px-3 py-3 text-gray-700">{{ implode(', ', $product->displayColors()) }}</td>
                                            <td class="px-3 py-3 whitespace-nowrap">
                                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                                    <a id="edit-product-{{ $product->id }}" href="{{ route('admin.products.edit', $product) }}" class="relative z-10 font-semibold text-blue-600 hover:text-blue-500">
                                                        {{ __('Edit') }}
                                                    </a>
                                                    <button
                                                        type="button"
                                                        id="delete-product-{{ $product->id }}"
                                                        class="relative z-10 font-semibold text-red-600 hover:text-red-500"
                                                        x-data=""
                                                        x-on:click.prevent="$dispatch('open-modal', 'confirm-product-deletion-{{ $product->id }}')"
                                                    >
                                                        {{ __('Delete') }}
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @foreach ($products as $product)
                            <x-modal name="confirm-product-deletion-{{ $product->id }}" maxWidth="lg" focusable>
                                <form method="POST" action="{{ route('admin.products.destroy', $product) }}" class="p-6">
                                    @csrf
                                    @method('DELETE')

                                    <h2 class="text-lg font-medium text-gray-900">
                                        {{ __('Delete product') }}
                                    </h2>

                                    <p class="mt-2 text-sm text-gray-600">
                                        {{ __('You are about to permanently delete :name (SKU :sku). This cannot be undone and will also remove the product images.', [
                                            'name' => $product->name,
                                            'sku' => $product->sku,
                                        ]) }}
                                    </p>

                                    <div class="mt-6 flex justify-end">
                                        <x-secondary-button x-on:click="$dispatch('close')">
                                            {{ __('Cancel') }}
                                        </x-secondary-button>

                                        <x-danger-button class="ms-3">
                                            {{ __('Delete') }}
                                        </x-danger-button>
                                    </div>
                                </form>
                            </x-modal>
                        @endforeach

                        <div class="mt-6">
                            {{ $products->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
