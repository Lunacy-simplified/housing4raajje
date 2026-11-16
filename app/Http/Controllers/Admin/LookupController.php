<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\District;
use App\Models\FeedbackCategory;
use App\Models\LeaseTerm;
use App\Models\PaymentMethod;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Master data: the short lists other screens choose from.
 * Items are switched off rather than deleted, so old records keep their meaning.
 */
class LookupController extends Controller
{
    /** type => [model, column shown as the name, heading] */
    const TYPES = [
        'districts' => [District::class, 'name', 'Districts'],
        'lease-terms' => [LeaseTerm::class, 'label', 'Lease lengths'],
        'payment-methods' => [PaymentMethod::class, 'name', 'Payment methods'],
        'feedback-categories' => [FeedbackCategory::class, 'label', 'Viewing feedback reasons'],
    ];

    public function index()
    {
        return view('admin.lookups', [
            'lists' => collect(self::TYPES)->map(fn ($type) => [
                'heading' => $type[2],
                'column' => $type[1],
                'items' => $type[0]::orderBy($type[1])->get(),
            ]),
            'branches' => Branch::orderBy('branch_no')->pluck('name', 'id'),
        ]);
    }

    public function store(Request $request, string $type)
    {
        [$model, $column] = self::TYPES[$type];
        $table = (new $model)->getTable();

        $data = $request->validate([
            $column => ['required', 'string', 'max:60', Rule::unique($table, $column)],
            'branch_id' => [Rule::requiredIf($type === 'districts'), Rule::exists('branches', 'id')],
            'months' => [Rule::requiredIf($type === 'lease-terms'), 'integer', 'between:1,120', Rule::unique('lease_terms', 'months')],
            'is_rejection' => ['nullable', 'boolean'],
        ]);

        $item = (new $model)->forceFill(match ($type) {
            'districts' => ['name' => $data['name'], 'branch_id' => $data['branch_id']],
            'lease-terms' => ['label' => $data['label'], 'months' => $data['months']],
            'payment-methods' => ['name' => $data['name'], 'code' => Str::snake($data['name'])],
            'feedback-categories' => ['label' => $data['label'], 'is_rejection' => $request->boolean('is_rejection'), 'sort_order' => FeedbackCategory::max('sort_order') + 1],
        });
        $item->save();

        Audit::log('lookup.create', $item);

        return back()->with('status', 'Added.');
    }

    public function toggle(string $type, int $id)
    {
        $item = self::TYPES[$type][0]::findOrFail($id);
        $item->is_active = ! $item->is_active;
        $item->save();

        Audit::log('lookup.toggle', $item, [], ['is_active' => $item->is_active]);

        return back()->with('status', $item->is_active ? 'Switched on.' : 'Switched off.');
    }
}
