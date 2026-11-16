<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Viewing;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ViewingController extends Controller
{
    public function index(Request $request)
    {
        return view('client.viewings', [
            'viewings' => $request->user()->viewings()->with('property.district')->latest('requested_at')->get(),
        ]);
    }

    /** Ask for a viewing. The branch then books a date and time. */
    public function store(Request $request, Property $property)
    {
        abort_unless($property->verification_status === 'approved' && $property->availability_status === 'available', 404);

        $user = $request->user();

        if ($user->viewings()->where('property_id', $property->id)->whereIn('status', ['requested', 'scheduled'])->exists()) {
            throw ValidationException::withMessages(['viewing' => 'You already have a viewing open for this property.']);
        }

        $viewing = new Viewing;
        $viewing->property_id = $property->id;
        $viewing->client_user_id = $user->id;
        $viewing->save();

        return back()->with('status', 'Viewing requested. The branch will confirm a date and time.');
    }

    public function cancel(Request $request, Viewing $viewing)
    {
        abort_unless($viewing->client_user_id === $request->user()->id, 404);
        abort_unless(in_array($viewing->status, ['requested', 'scheduled'], true), 403);

        $viewing->status = 'cancelled';
        $viewing->save();

        return back()->with('status', 'Viewing cancelled.');
    }
}
