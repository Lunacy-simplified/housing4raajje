{{-- A multi-line text field. <x-textarea name="description" label="What is wrong?" required /> --}}
@props(['name', 'label', 'value' => null, 'hint' => null])
<x-field :name="$name" :label="$label" :hint="$hint">
    <textarea id="{{ $name }}" name="{{ $name }}" rows="3" {{ $attributes->class(['in', 'bad' => $errors->has($name)]) }}>{{ old($name, $value) }}</textarea>
</x-field>
