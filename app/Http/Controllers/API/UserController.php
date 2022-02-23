<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserStoreFormRequest;
use App\Http\Requests\UserUpdateFormRequest;
use App\Http\Resources\UserCollection;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $search = $request->q;
        $wildSearch = "%$search%";

        $query = User::with('outlet')->courier();
        $query->when($search, function ($q) use ($wildSearch) {
            $q->where('name', 'LIKE', $wildSearch);
        });

        $users = $query->paginate();
        return new UserCollection($users);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(UserStoreFormRequest $request)
    {
        $fileName = null;

        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $fileName = $request->email . '-' . time() . '-' . $file->getClientOriginalExtension();
            $file->storeAs('public/couriers', $fileName);
        }

        $user = User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => \Hash::make($request->password),
            'role'      => 3,                                 //daftar sebagai courier
            'photo'     => $fileName,
            'outlet_id' => $request->outlet_id,
        ]);

        return new UserResource($user);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function show(User $courier)
    {
        $user = $courier->load('outlet');
        return new UserResource($user);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(UserUpdateFormRequest $request, User $courier)
    {
        // dd($request->all());

        // $password = $request->password ?? \Hash::make($request->password);
        $fileName = $courier->photo;

        if ($request->hasFile('photo')) {
            Storage::delete('public/couriers/' . $fileName);

            $file = $request->file('photo');
            $fileName = $courier->email . '-' . time() . '-' . $file->getClientOriginalExtension();
            $file->storeAs('public/couriers', $fileName);
        }

        if ($request->password != null || $request->password != '') {
            $courier->password  = \Hash::make($request->password);
        }

        $courier->name      = $request->name;
        $courier->photo     = $fileName;
        $courier->outlet_id = $request->outlet_id;
        $courier->save();

        return new UserResource($courier);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $courier)
    {
        $courier->delete();
        return new UserResource($courier);
    }
}
