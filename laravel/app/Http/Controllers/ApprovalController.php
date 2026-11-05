<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Models\Event;
use App\Models\Department;
use App\Models\Schedule;
use App\Models\Slot;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;

class ApprovalController extends Controller
{
    // Admins only
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
        $this->middleware('bindings');
    }

    // Pick an event + role, then see who's tagged and who has shifts
    public function index(Request $request)
    {
        $events = Event::orderBy('start_date', 'desc')->get();
        $roles = Role::orderBy('name')->get();
        $builtIn = RoleController::BUILT_IN;

        $event = $request->query('event') ? Event::find($request->query('event')) : null;
        $role = $request->query('role') ? Role::find($request->query('role')) : null;

        $approvedScheduled = collect();
        $approvedNoShifts = collect();
        $notApprovedScheduled = collect();
        $shiftCounts = collect();

        if($event && $role)
        {
            // Every schedule in this event
            $departmentIds = Department::where('event_id', $event->id)->pluck('id');
            $scheduleIds = Schedule::whereIn('department_id', $departmentIds)->pluck('id');

            // user_id => number of shifts they hold in this event
            $shiftCounts = Slot::whereIn('schedule_id', $scheduleIds)
                ->whereNotNull('user_id')
                ->selectRaw('user_id, count(*) as total')
                ->groupBy('user_id')
                ->pluck('total', 'user_id');

            // Everyone with the chosen role
            $taggedIds = UserRole::where('role_id', $role->id)->pluck('user_id')->unique();
            $tagged = User::with('data')->whereIn('id', $taggedIds)->orderBy('name')->get();

            $approvedScheduled = $tagged->filter(function($user) use ($shiftCounts) { return isset($shiftCounts[$user->id]); })->values();
            $approvedNoShifts = $tagged->filter(function($user) use ($shiftCounts) { return !isset($shiftCounts[$user->id]); })->values();

            $untaggedIds = $shiftCounts->keys()->diff($taggedIds);
            $notApprovedScheduled = User::with('data')->whereIn('id', $untaggedIds)->orderBy('name')->get();
        }

        return view('pages/admin/approvals', compact(
            'events', 'roles', 'builtIn', 'event', 'role',
            'approvedScheduled', 'approvedNoShifts', 'notApprovedScheduled', 'shiftCounts'
        ));
    }

    // Tag a pasted list of emails/usernames with a role
    public function tagList(Request $request)
    {
        $role = $this->customRole($request);

        if(!$role)
        {
            return $this->back($request);
        }

        // One per line (commas, semicolons and tabs also work)
        $entries = preg_split('/[\r\n,;\t]+/', $request->get('list', ''));
        $entries = array_unique(array_filter(array_map(function($entry) { return strtolower(trim($entry)); }, $entries)));

        $added = [];
        $already = [];
        $notFound = [];

        foreach($entries as $entry)
        {
            $user = User::whereRaw('LOWER(email) = ?', [$entry])->first()
                 ?: User::whereRaw('LOWER(name) = ?', [$entry])->first();

            if(!$user)
            {
                $notFound[] = $entry;
            }
            elseif(UserRole::where('role_id', $role->id)->where('user_id', $user->id)->exists())
            {
                $already[] = $user->name;
            }
            else
            {
                UserRole::create(['role_id' => $role->id, 'user_id' => $user->id]);
                $added[] = $user->name;
            }
        }

        $request->session()->flash('tag_result', compact('added', 'already', 'notFound'));
        return $this->back($request);
    }

    // Tag one user (button on the report)
    public function tagUser(Request $request)
    {
        $role = $this->customRole($request);
        $user = User::find($request->get('user'));

        if($role && $user && !UserRole::where('role_id', $role->id)->where('user_id', $user->id)->exists())
        {
            UserRole::create(['role_id' => $role->id, 'user_id' => $user->id]);
            $request->session()->flash('success', 'Tagged ' . $user->name . ' with "' . $role->name . '".');
        }

        return $this->back($request);
    }

    // Remove the tag from one user (button on the report)
    public function untagUser(Request $request)
    {
        $role = $this->customRole($request);
        $user = User::find($request->get('user'));

        if($role && $user)
        {
            UserRole::where('role_id', $role->id)->where('user_id', $user->id)->delete();
            $request->session()->flash('success', 'Removed "' . $role->name . '" from ' . $user->name . '.');
        }

        return $this->back($request);
    }

    // Only custom roles can be added/removed from this page, so nobody
    // accidentally removes their own admin role from here
    private function customRole(Request $request)
    {
        $role = Role::find($request->get('role'));

        if(!$role)
        {
            $request->session()->flash('error', 'Pick a role first.');
            return null;
        }

        if(in_array($role->name, RoleController::BUILT_IN))
        {
            $request->session()->flash('error', 'Built-in roles like "' . $role->name . '" can only be changed on a user\'s profile page.');
            return null;
        }

        return $role;
    }

    // Back to the report with the same event + role selected
    private function back(Request $request)
    {
        return redirect('/approvals?' . http_build_query([
            'event' => $request->get('event'),
            'role' => $request->get('role'),
        ]));
    }
}
