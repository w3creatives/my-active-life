<?php
declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Utilities\DataTable;
use Illuminate\Http\Request;

use App\Models\User;

class UsersController extends Controller
{
    public function index(Request $request, DataTable $dataTable)
    {

        if ($request->ajax()) {

            $query = User::select(['first_name', 'last_name', 'email', 'display_name', 'id'])
                ->where('super_admin', false);

            list($userCount, $users) = $dataTable->setSearchableColumns(['first_name', 'last_name', 'email', 'display_name'])
                ->query($request, $query)->response();

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
