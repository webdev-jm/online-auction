<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Company;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;

use App\Http\Requests\UserAddRequest;
use App\Http\Requests\UserEditRequest;

use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::with(['company', 'roles'])
            ->orderBy('created_at', 'DESC')
            ->paginate(10)->appends(request()->query());

        return view('pages.users.index')->with([
            'users' => $users
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $companies = Company::all();
        $companies_arr = [];
        foreach($companies as $company) {
            $companies_arr[encrypt($company->id)] = $company->name;
        }

        $roles = Role::orderBy('name', 'ASC')
            ->get();

        return view('pages.users.create')->with([
            'companies' => $companies_arr,
            'roles' => $roles
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UserAddRequest $request)
    {
        $email_arr = explode('@', $request->email);
        $password = Hash::make(reset($email_arr).'123!');

        $user = new User([
            'company_id' => decrypt($request->company_id),
            'name' => $request->name,
            'email' => $request->email,
            'password' => $password,
        ]);
        $user->save();

        $role_ids = explode(',', $request->role_ids);
        $user->assignRole($role_ids);

        // logs
        activity('created')
            ->performedOn($user)
            ->log(':causer.name has created user :subject.name');

        return redirect()->route('user.index')->with([
            'message_success' => 'User '.$user->name.' has been successfully created.'
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $user = User::findOrFail(decrypt($id));

        return view('pages.users.show')->with([
            'user' => $user
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $user = User::findOrFail(decrypt($id));

        $companies = Company::all();
        $companies_arr = [];
        $company_selected_id = '';
        foreach($companies as $company) {
            $encrypted_id = encrypt($company->id);
            if($user->company_id == $company->id) {
                $company_selected_id = $encrypted_id;
            }

            $companies_arr[$encrypted_id] = $company->name;
        }

        $roles = Role::orderBy('name', 'ASC')
            ->get();

        $user_roles = $user->roles->pluck('name')->toArray();

        return view('pages.users.edit')->with([
            'user' => $user,
            'companies' => $companies_arr,
            'roles' => $roles,
            'company_selected_id' => $company_selected_id,
            'user_roles' => $user_roles
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UserEditRequest $request, $id)
    {
        $user = User::findOrFail(decrypt($id));
        $user_roles = $user->roles->pluck('name')->toArray();

        $changes_arr['old'] = $user->getOriginal();
        $changes_arr['old']['arr'] = $user->roles->pluck('name');

        $user->update([
            'company_id' => decrypt($request->company_id),
            'name' => $request->name,
            'email' => $request->email,
        ]);
        
        $role_ids = explode(',', $request->role_ids);
        $user->syncRoles($role_ids);

        $changes_arr['changes'] = $user->getChanges();
        $changes_arr['changes']['arr'] = $user->roles->pluck('name');

        // logs
        activity('updated')
            ->performedOn($user)
            ->withProperties($changes_arr)
            ->log(':causer.name has updated user :subject.name');

        return back()->with([
            'message_success' => 'User '.$user->name.' has been updated successfully.'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        //
    }
}
