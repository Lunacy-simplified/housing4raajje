{{-- The facts of a property as label and value pairs. Needs $property with district and branch loaded. --}}
<dl class="kv">
    <dt>Reference</dt>
    <dd class="mono">{{ $property->reference }}</dd>
    <dt>Address</dt>
    <dd>{{ $property->address() }}</dd>
    <dt>Type</dt>
    <dd>{{ ucfirst($property->property_type) }}@if ($property->floor !== null), floor {{ $property->floor }}@endif</dd>
    <dt>Rooms</dt>
    <dd>{{ $property->rooms }}</dd>
    <dt>Monthly rent</dt>
    <dd>MVR {{ number_format($property->monthly_rent, 2) }}</dd>
    <dt>Branch</dt>
    <dd>{{ $property->branch->name }}</dd>
</dl>
