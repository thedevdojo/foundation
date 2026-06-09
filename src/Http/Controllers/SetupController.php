<?php

namespace Devdojo\Foundation\Http\Controllers;

use Devdojo\Foundation\Foundation;
use Illuminate\Http\Request;

class SetupController extends Controller
{
    public function index()
    {
        return view('foundation::setup', [
            'features' => Foundation::features(),
            'depends' => config('foundation.depends', []),
        ]);
    }

    public function update(Request $request)
    {
        $submitted = (array) $request->input('features', []);

        foreach (array_keys(config('foundation.features', [])) as $feature) {
            // 'auth' is foundational and cannot be disabled here.
            if ($feature === 'auth') {
                Foundation::setFeature('auth', true);

                continue;
            }

            Foundation::setFeature($feature, array_key_exists($feature, $submitted));
        }

        return redirect()->route('foundation.setup')->with('status', 'Features updated.');
    }
}
