@php
    $fieldClass = 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500';
@endphp

<div>
    <label for="name" class="block text-sm font-medium text-gray-700">{{ __('Name') }}</label>
    <input id="name" name="name" type="text" value="{{ $name }}" maxlength="255" autocomplete="off" class="{{ $fieldClass }}">
    <x-input-error class="mt-2" :messages="$errors->get('name')" />
</div>

<div>
    <label for="price" class="block text-sm font-medium text-gray-700">{{ __('Price (BRL)') }}</label>
    <input id="price" name="price" type="number" value="{{ $price }}" inputmode="decimal" step="0.01" min="0" max="99999999.99" class="{{ $fieldClass }}">
    <x-input-error class="mt-2" :messages="$errors->get('price')" />
</div>

<div>
    <label for="colors" class="block text-sm font-medium text-gray-700">{{ __('Colors') }}</label>
    <select id="colors" name="colors[]" multiple size="4" class="{{ $fieldClass }}">
        @foreach ($colors as $color)
            <option value="{{ $color }}" @selected(in_array($color, $selectedColors, true))>{{ $color }}</option>
        @endforeach
    </select>
    <p class="mt-1 text-xs text-gray-500">{{ __('Hold Ctrl or Cmd to select more than one color.') }}</p>
    <x-input-error class="mt-2" :messages="$errors->get('colors')" />
    <x-input-error class="mt-2" :messages="$errors->get('colors.*')" />
</div>

<div>
    <label for="short_description" class="block text-sm font-medium text-gray-700">{{ __('Short Description') }}</label>
    <input id="short_description" name="short_description" type="text" value="{{ $shortDescription }}" maxlength="500" class="{{ $fieldClass }}">
    <x-input-error class="mt-2" :messages="$errors->get('short_description')" />
</div>

<div>
    <label for="qty" class="block text-sm font-medium text-gray-700">{{ __('Qty') }}</label>
    <input id="qty" name="qty" type="number" value="{{ $qty }}" inputmode="numeric" step="1" min="0" class="{{ $fieldClass }}">
    <x-input-error class="mt-2" :messages="$errors->get('qty')" />
</div>

<div>
    <label for="sku" class="block text-sm font-medium text-gray-700">{{ __('SKU') }}</label>
    <input id="sku" name="sku" type="text" value="{{ $sku }}" maxlength="100" inputmode="text" autocomplete="off" class="{{ $fieldClass }}">
    <x-input-error class="mt-2" :messages="$errors->get('sku')" />
</div>

<div>
    <label for="description" class="block text-sm font-medium text-gray-700">{{ __('Description') }}</label>
    <textarea id="description" name="description" class="sr-only" rows="1" tabindex="-1">{{ $description }}</textarea>
    <div id="description-editor" class="mt-1 bg-white"></div>
    <x-input-error class="mt-2" :messages="$errors->get('description')" />
</div>
