<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;

use App\Models\Role;
use App\Models\UserRole;
use App\Models\EventRole;

class RoleController extends Controller
{
    // Roles that came with the app. The code checks some of these by name
    // (admin, department-lead, volunteer, banned...), so they can't be deleted here.
    const BUILT_IN = [
        'admin',
        'volunteer',
        'medical',
        'fire',
        'banned',
        'ranger',
        'ranger-khaki',
        'event-admin',
        'department-lead',
        'photography',
        'board-member',
    ];

    // Admins only
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
        $this->middleware('bindings');
    }

    // List all roles, with how many users have each one
    public function index()
    {
        $roles = Role::orderBy('name')->get();
        $userCounts = UserRole::selectRaw('role_id, count(distinct user_id) as total')->groupBy('role_id')->pluck('total', 'role_id');
        $shiftCounts = EventRole::selectRaw('role_id, count(*) as total')->groupBy('role_id')->pluck('total', 'role_id');
        $builtIn = self::BUILT_IN;

        return view('pages/admin/roles', compact('roles', 'userCounts', 'shiftCounts', 'builtIn'));
    }

    // Create a new custom role
    public function create(Request $request)
    {
        $name = strtolower(trim($request->get('name', '')));

        $validator = Validator::make(['name' => $name],
        [
            'name' => 'required|max:50|regex:/^[a-z0-9-]+$/|unique:roles,name',
        ],
        [
            'name.required' => 'Enter a name for the role.',
            'name.regex' => 'Role names can only use lowercase letters, numbers and dashes (e.g. aitp-2026).',
            'name.unique' => 'A role named "' . $name . '" already exists.',
        ]);

        if($validator->fails())
        {
            $request->session()->flash('error', $validator->errors()->first('name'));
            return redirect('/roles');
        }

        Role::create(['name' => $name]);

        $request->session()->flash('success', 'Created the "' . $name . '" role.');
        return redirect('/roles');
    }

    // Delete a custom role, removing it from every user and every shift's allowed roles
    public function delete(Request $request, Role $role)
    {
        if(in_array($role->name, self::BUILT_IN))
        {
            $request->session()->flash('error', 'The "' . $role->name . '" role is built into the app and can\'t be deleted.');
            return redirect('/roles');
        }

        $name = $role->name;

        // The database would cascade these anyway; removing them explicitly makes it certain
        UserRole::where('role_id', $role->id)->delete();
        EventRole::where('role_id', $role->id)->delete();
        $role->delete();

        $request->session()->flash('success', 'Deleted the "' . $name . '" role.');
        return redirect('/roles');
    }
}
