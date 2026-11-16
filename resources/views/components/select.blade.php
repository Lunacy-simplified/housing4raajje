{{--
    A dropdown field. $options is [value => label].
        <x-select name="district_id" label="District" :options="$districts" placeholder="Choose a district" required />
--}}
@props(['name', 'label', 'options', 'value' => null, 'placeholder' => null, 'hint' => null])
<x-field :name="$name" :label="$label" :hint="$hint">
    <select id="{{ $name }}" name="{{ $name }}" {{ $attributes->class(['in', 'bad' => $errors->has($name)]) }}>
        @if ($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $key => $text)
            <option value="{{ $key }}" @selected((string) old($name, $value) === (string) $key)>{{ $text }}</option>
        @endforeach
    </select>
</x-field>
