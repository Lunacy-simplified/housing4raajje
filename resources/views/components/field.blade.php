{{--
    One form field: label, control, hint and error. See docs/design.md.
        <x-field name="road" label="Road" :value="$property->road" required />
    Pass a control in the slot to replace the input (select, textarea).
--}}
@props(['name', 'label', 'type' => 'text', 'value' => null, 'hint' => null])
<div class="fld">
    <label for="{{ $name }}">{{ $label }}</label>
    @if ($slot->isNotEmpty())
        {{ $slot }}
    @else
        <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" @if ($type !== 'password') value="{{ old($name, $value) }}" @endif
            {{ $attributes->class(['in', 'bad' => $errors->has($name)]) }}>
    @endif
    @if ($hint)
        <span class="hint">{{ $hint }}</span>
    @endif
    @error($name)
        <span class="err" role="alert">{{ $message }}</span>
    @enderror
</div>
