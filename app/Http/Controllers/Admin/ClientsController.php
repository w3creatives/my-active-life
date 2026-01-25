<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Event;
use App\Models\User;
use App\Utilities\DataTable;
use Illuminate\Http\Request;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;

final class ClientsController extends Controller
{
    public function index(Request $request, DataTable $dataTable)
    {
        if ($request->ajax()) {

            $query = Client::select(['name', 'logo', 'address', 'id', 'is_active']);

            [$itemCount, $clients] = $dataTable->setSearchableColumns(['name', 'address'])
                ->query($request, $query)->response();

            $clients = $clients->map(function ($client) {
                $client->logo = sprintf('<div class="avatar avatar-lg me-2"><img class="img-thumbnail rounded-circle" src="%s"/></div>', $client->logo_url);
                $client->address = sprintf('<span class="white-space-break">%s</span>', $client->address);
                $client->action = [
                    view('admin.clients.actions.client', compact('client'))->render(),
                ];

                return $client;
            });

            return response()->json([
                'draw' => $request->get('draw'),
                'recordsTotal' => $itemCount,
                'recordsFiltered' => $itemCount,
                'data' => $clients,
            ]);

        }

        return view('admin.clients.list');
    }

    public function create(Request $request)
    {
        $client = Client::find($request->route()->parameter('id'));

        return view('admin.clients.create', compact('client'));
    }

    public function store(Request $request)
    {

        $client = Client::find($request->route()->parameter('id'));

        $request->validate([
            'name' => 'required',
            'address' => 'required',
            'logo' => [
                'image',
                'mimes:jpeg,png,jpg,gif,svg',
                'max:2048',
            ],
            'is_active' => 'required|boolean',
        ]);

        $data = $request->only(['name', 'address', 'is_active']);

        $data['is_active'] = (int)$data['is_active'] === 1;

        if ($request->hasFile('logo')) {
            $logoFile = $request->file('logo');
            $logoFileName = 'client_' . time() . '_' . uniqid() . '.' . $logoFile->getClientOriginalExtension();
            $logoFile->storeAs('uploads/clients', $logoFileName, 'public');
            $data['logo'] = $logoFileName;
        }

        $flashMessage = sprintf('Client details %s successfully.!.', $client ? 'updated' : 'created');

        if ($client) {
            $client->fill($data)->save();
        } else {
            $client = Client::create($data);
        }

        return redirect()->route('admin.clients')->with('alert', ['type' => 'success', 'message' => $flashMessage]);

    }

    public function show(Request $request)
    {
        $client = Client::findOrFail($request->route()->parameter('id'));

        $activeTab = $request->get('tab', 'events');

        $tabs = [
            [
                'name' => 'events',
                'url' => route('admin.clients.events', $client->id),
                'columns' => [
                    ['title' => 'Name', 'name' => 'name', 'data' => 'name'],
                    ['title' => 'Start Date', 'name' => 'start_date', 'data' => 'start_date'],
                    ['title' => 'End Date', 'name' => 'end_date', 'data' => 'end_date'],
                ],
            ],
            [
                'name' => 'users',
                'url' => route('admin.clients.users', $client->id),
                'columns' => [
                    ['title' => 'First Name', 'name' => 'first_name', 'data' => 'first_name'],
                    ['title' => 'Last Name', 'name' => 'last_name', 'data' => 'last_name'],
                    ['title' => 'Display Name', 'name' => 'display_name', 'data' => 'display_name'],
                    ['title' => 'Email Address', 'name' => 'email', 'data' => 'email'],
                ],
            ],
        ];

        return view('admin.clients.show', compact('client', 'tabs','activeTab'));
    }

    public function events(Request $request, DataTable $dataTable)
    {
        if (!$request->ajax()) {
            throw new MethodNotAllowedException('Not allowed');
        }

        $clientId = $request->route()->parameter('id');

        $query = Event::query()->select(['name', 'start_date', 'end_date', 'id'])
            ->whereHas('clients', function ($query) use ($clientId) {
                $query->where('client_id', $clientId);
            });

        [$itemCount, $items] = $dataTable->setSearchableColumns(['name'])
            ->query($request, $query)->response();

        return response()->json([
            'draw' => $request->get('draw'),
            'recordsTotal' => $itemCount,
            'recordsFiltered' => $itemCount,
            'data' => $items,
        ]);

    }

    public function users(Request $request, DataTable $dataTable)
    {
        if (!$request->ajax()) {
            throw new MethodNotAllowedException('Not allowed');
        }

        $clientId = $request->route()->parameter('id');

        $query = User::query()->select(['first_name', 'last_name', 'display_name', 'email', 'id'])
            ->whereHas('clients', function ($query) use ($clientId) {
                $query->where('client_id', $clientId);
            });

        [$itemCount, $items] = $dataTable->setSearchableColumns(['first_name', 'last_name', 'display_name', 'email'])
            ->query($request, $query)->response();

        return response()->json([
            'draw' => $request->get('draw'),
            'recordsTotal' => $itemCount,
            'recordsFiltered' => $itemCount,
            'data' => $items,
        ]);

    }
}
