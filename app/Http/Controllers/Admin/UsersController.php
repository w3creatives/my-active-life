<?php
declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\User;

class UsersController extends Controller
{
    public function index(Request $request){

        if($request->ajax()){

            $searchTerm = $request->input('search.value');

            $users = User::select(['first_name','last_name','email','display_name','id'])
            ->where('super_admin',false)
            ->where(function($query) use ($searchTerm) {

                if($searchTerm){
                    $query->where('first_name','ILIKE',"%{$searchTerm}%")
                    ->orWhere('last_name','ILIKE',"%{$searchTerm}%")
                    ->orWhere('display_name','ILIKE',"%{$searchTerm}%")
                    ->orWhere('email','ILIKE',"%{$searchTerm}%");
                }

                return $query;
            });

            $userCount = $users->count();

            $users = $users->limit($request->get('limit', 10))
                ->skip($request->get('offset', 0))
                ->get();

            return response()->json([
                'draw' => $request->get('draw'),
                'recordsTotal' => $userCount,
                'recordsFiltered' => $userCount,
                'data' => $users
            ]);

        }
        return view('admin.users.list');
    }
}
