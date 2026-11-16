{{-- "Escalate to manager" form. Pass $type (lease, application or maintenance) and $id. --}}
<details>
    <summary>Escalate to manager</summary>
    <form method="POST" action="{{ route('staff.escalations.store') }}" class="inline">
        @csrf
        <input type="hidden" name="subject_type" value="{{ $type }}">
        <input type="hidden" name="subject_id" value="{{ $id }}">
        <input name="reason" class="in" placeholder="Why does the manager need to step in?" aria-label="Reason" required maxlength="1000">
        <button type="submit" class="btn btn-n">Escalate</button>
    </form>
</details>
