<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemParameter;
use App\Support\Audit;
use Illuminate\Http\Request;

/** Global parameters, e.g. the management fee percentage. */
class SettingController extends Controller
{
    public function index()
    {
        return view('admin.settings', ['settings' => SystemParameter::orderBy('key')->get()]);
    }

    public function update(Request $request, SystemParameter $setting)
    {
        $data = $request->validate(['value' => ['required', 'numeric', 'between:0,365']]);

        $old = $setting->value;
        $setting->value = $data['value'];
        $setting->updated_by_user_id = $request->user()->id;
        $setting->save();

        Audit::log('setting.update', $setting, ['value' => $old], ['value' => $data['value']]);

        return back()->with('status', "Setting {$setting->key} updated.");
    }
}
