<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SchoolInformation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SchoolInformationController extends Controller
{
    public function edit(): View
    {
        return view('users.admin.school-information', ['school' => SchoolInformation::current()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'school_id' => ['required', 'digits:6'],
            'region' => ['required', 'string', 'max:100'],
            'division' => ['required', 'string', 'max:100'],
            'district' => ['required', 'string', 'max:100'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048', 'dimensions:max_width=3000,max_height=3000'],
            'remove_logo' => ['nullable', 'boolean'],
        ]);
        $newPath = null;

        try {
            $newPath = $request->hasFile('logo') ? $request->file('logo')->store('school-logos', 'public') : null;
            if ($newPath === false) {
                throw new \RuntimeException('The school logo could not be stored.');
            }
            $oldPath = DB::transaction(function () use ($request, $data, $newPath) {
                $school = SchoolInformation::query()->firstOrCreate(['id' => 1], ['name' => config('app.name')]);
                $school = SchoolInformation::query()->lockForUpdate()->findOrFail($school->id);
                $oldPath = $school->logo_path;
                unset($data['logo'], $data['remove_logo']);
                $school->fill($data);
                if ($newPath || $request->boolean('remove_logo')) {
                    $school->logo_path = $newPath;
                }
                $school->save();

                return $oldPath !== $school->logo_path ? $oldPath : null;
            });
        } catch (\Throwable $exception) {
            report($exception);
            if ($newPath) {
                $this->deleteLogo($newPath);
            }

            return back()->withInput($request->except('logo'))
                ->with('error', 'School information could not be saved. Please try again. If you selected a logo, select it again.');
        }
        if ($oldPath) {
            $this->deleteLogo($oldPath);
        }

        return back()->with('success', 'School information saved successfully.');
    }

    private function deleteLogo(string $path): void
    {
        try {
            Storage::disk('public')->delete($path);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
