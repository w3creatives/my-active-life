import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Follow',
        href: route('follow'),
    },
];

// Mock data for demonstration
const followedPeople = [
    { id: 1, name: 'Jane Smith', progress: 75, miles: 125.5, isFollowing: true },
    { id: 2, name: 'John Doe', progress: 45, miles: 78.2, isFollowing: true },
    { id: 3, name: 'Alex Johnson', progress: 90, miles: 156.8, isFollowing: true },
];

const followedTeams = [
    { id: 1, name: 'Road Runners', progress: 82, miles: 432.1, isFollowing: true },
    { id: 2, name: 'Marathon Masters', progress: 63, miles: 287.5, isFollowing: true },
];

// Mock data for people to follow
const peopleToFollow = [
    { id: 4, name: 'Sarah Williams', city: 'Denver', state: 'CO', isFollowing: false },
    { id: 5, name: 'Michael Brown', city: 'Seattle', state: 'WA', isFollowing: false },
    { id: 6, name: 'Emily Davis', city: 'Boston', state: 'MA', isFollowing: false },
    { id: 7, name: 'David Wilson', city: 'Chicago', state: 'IL', isFollowing: false },
    { id: 8, name: 'Lisa Taylor', city: 'Austin', state: 'TX', isFollowing: false },
];

// Mock data for teams to follow
const teamsToFollow = [
    { id: 3, name: 'Speed Demons', members: 12, miles: 567.8, isFollowing: false },
    { id: 4, name: 'Trail Blazers', members: 8, miles: 342.5, isFollowing: false },
    { id: 5, name: 'Pace Setters', members: 15, miles: 789.3, isFollowing: false },
    { id: 6, name: 'Mile Munchers', members: 6, miles: 234.7, isFollowing: false },
    { id: 7, name: 'Endurance Elite', members: 10, miles: 456.2, isFollowing: false },
];

export default function Dashboard() {
    const [peopleSearch, setPeopleSearch] = useState('');
    const [teamSearch, setTeamSearch] = useState('');
    const [peoplePerPage, setPeoplePerPage] = useState(5);
    const [teamsPerPage, setTeamsPerPage] = useState(5);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Follow" />
            <div className="flex flex-col gap-6 p-4">
                {/* People I Follow Section */}
                <div className="bg-white rounded-lg shadow-sm p-6">
                    <h2 className="text-xl font-semibold mb-4">People I Follow</h2>
                    {followedPeople.length > 0 ? (
                        <div className="overflow-x-auto">
                            <table className="w-full">
                                <tbody>
                                    {followedPeople.map((person) => (
                                        <tr key={person.id} className="border-b last:border-b-0">
                                            <td className="py-4 pr-4 w-48">
                                                <div className="flex items-center gap-3">
                                                    <div className="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center text-gray-500">
                                                        {person.name.charAt(0)}
                                                    </div>
                                                    <span className="font-medium">{person.name}</span>
                                                </div>
                                            </td>
                                            <td className="py-4 px-4 w-full">
                                                <div className="w-full bg-gray-200 rounded-full h-2.5">
                                                    <div
                                                        className="bg-blue-600 h-2.5 rounded-full"
                                                        style={{ width: `${person.progress}%` }}
                                                    ></div>
                                                </div>
                                            </td>
                                            <td className="py-4 px-4 whitespace-nowrap">
                                                <span className="font-medium">{person.miles.toFixed(1)} miles</span>
                                            </td>
                                            <td className="py-4 pl-4">
                                                <button
                                                    className="px-4 py-2 rounded-md bg-red-500 text-white hover:bg-red-600 transition-colors"
                                                >
                                                    Unfollow
                                                </button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : (
                        <p className="text-gray-500">You are not following anyone.</p>
                    )}
                </div>

                {/* Teams I Follow Section */}
                <div className="bg-white rounded-lg shadow-sm p-6">
                    <h2 className="text-xl font-semibold mb-4">Teams I Follow</h2>
                    {followedTeams.length > 0 ? (
                        <div className="overflow-x-auto">
                            <table className="w-full">
                                <tbody>
                                    {followedTeams.map((team) => (
                                        <tr key={team.id} className="border-b last:border-b-0">
                                            <td className="py-4 pr-4 w-48">
                                                <div className="flex items-center gap-3">
                                                    <div className="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center text-gray-500">
                                                        {team.name.charAt(0)}
                                                    </div>
                                                    <span className="font-medium">{team.name}</span>
                                                </div>
                                            </td>
                                            <td className="py-4 px-4 w-full">
                                                <div className="w-full bg-gray-200 rounded-full h-2.5">
                                                    <div
                                                        className="bg-green-600 h-2.5 rounded-full"
                                                        style={{ width: `${team.progress}%` }}
                                                    ></div>
                                                </div>
                                            </td>
                                            <td className="py-4 px-4 whitespace-nowrap">
                                                <span className="font-medium">{team.miles.toFixed(1)} miles</span>
                                            </td>
                                            <td className="py-4 pl-4">
                                                <button
                                                    className="px-4 py-2 rounded-md bg-red-500 text-white hover:bg-red-600 transition-colors"
                                                >
                                                    Unfollow
                                                </button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : (
                        <p className="text-gray-500">You are not following any teams.</p>
                    )}
                </div>

                {/* Choose People To Follow Section */}
                <div className="bg-white rounded-lg shadow-sm p-6">
                    <h2 className="text-xl font-semibold mb-4">Choose People To Follow</h2>
                    <p className="text-gray-600 mb-4">
                        If you want to follow somebody, browse below and follow. If a person has a private profile, you must be approved to follow.
                    </p>

                    <div className="flex flex-wrap gap-4 mb-4">
                        <div className="flex-1 min-w-[200px]">
                            <div className="relative">
                                <div className="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <svg className="w-4 h-4 text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                        <path stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                                    </svg>
                                </div>
                                <input
                                    type="search"
                                    className="block w-full p-2.5 pl-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Search People..."
                                    value={peopleSearch}
                                    onChange={(e) => setPeopleSearch(e.target.value)}
                                />
                            </div>
                        </div>
                        <div>
                            <select
                                className="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                value={peoplePerPage}
                                onChange={(e) => setPeoplePerPage(Number(e.target.value))}
                            >
                                <option value="5">5 per page</option>
                                <option value="10">10 per page</option>
                                <option value="25">25 per page</option>
                                <option value="50">50 per page</option>
                            </select>
                        </div>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="bg-gray-50 text-xs uppercase text-gray-700">
                                <tr>
                                    <th className="px-6 py-3 text-left">Name</th>
                                    <th className="px-6 py-3 text-left">City</th>
                                    <th className="px-6 py-3 text-left">State</th>
                                    <th className="px-6 py-3 text-right"></th>
                                </tr>
                            </thead>
                            <tbody>
                                {peopleToFollow.map((person) => (
                                    <tr key={person.id} className="border-b last:border-b-0">
                                        <td className="px-6 py-4">
                                            <div className="flex items-center gap-3">
                                                <div className="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center text-gray-500">
                                                    {person.name.charAt(0)}
                                                </div>
                                                <span className="font-medium">{person.name}</span>
                                            </div>
                                        </td>
                                        <td className="px-6 py-4">{person.city}</td>
                                        <td className="px-6 py-4">{person.state}</td>
                                        <td className="px-6 py-4 text-right">
                                            <button
                                                className="px-4 py-2 rounded-md bg-blue-500 text-white hover:bg-blue-600 transition-colors"
                                            >
                                                Follow
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    <div className="flex justify-between items-center mt-4">
                        <div className="text-sm text-gray-700">
                            Showing <span className="font-medium">1</span> to <span className="font-medium">{peoplePerPage}</span> of <span className="font-medium">{peopleToFollow.length}</span> results
                        </div>
                        <div className="flex gap-1">
                            <button className="px-3 py-1 border rounded-md bg-gray-100 hover:bg-gray-200">Previous</button>
                            <button className="px-3 py-1 border rounded-md bg-gray-100 hover:bg-gray-200">Next</button>
                        </div>
                    </div>
                </div>

                {/* Choose Teams To Follow Section */}
                <div className="bg-white rounded-lg shadow-sm p-6">
                    <h2 className="text-xl font-semibold mb-4">Choose Teams To Follow</h2>
                    <p className="text-gray-600 mb-4">
                        If you want to follow a team, browse below and follow. If a team has a private profile, you must be approved to follow them.
                    </p>

                    <div className="flex flex-wrap gap-4 mb-4">
                        <div className="flex-1 min-w-[200px]">
                            <div className="relative">
                                <div className="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <svg className="w-4 h-4 text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                        <path stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                                    </svg>
                                </div>
                                <input
                                    type="search"
                                    className="block w-full p-2.5 pl-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Search Teams..."
                                    value={teamSearch}
                                    onChange={(e) => setTeamSearch(e.target.value)}
                                />
                            </div>
                        </div>
                        <div>
                            <select
                                className="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                value={teamsPerPage}
                                onChange={(e) => setTeamsPerPage(Number(e.target.value))}
                            >
                                <option value="5">5 per page</option>
                                <option value="10">10 per page</option>
                                <option value="25">25 per page</option>
                                <option value="50">50 per page</option>
                            </select>
                        </div>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="bg-gray-50 text-xs uppercase text-gray-700">
                                <tr>
                                    <th className="px-6 py-3 text-left">Team</th>
                                    <th className="px-6 py-3 text-left">Members</th>
                                    <th className="px-6 py-3 text-left">Mileage</th>
                                    <th className="px-6 py-3 text-right"></th>
                                </tr>
                            </thead>
                            <tbody>
                                {teamsToFollow.map((team) => (
                                    <tr key={team.id} className="border-b last:border-b-0">
                                        <td className="px-6 py-4">
                                            <div className="flex items-center gap-3">
                                                <div className="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center text-gray-500">
                                                    {team.name.charAt(0)}
                                                </div>
                                                <span className="font-medium">{team.name}</span>
                                            </div>
                                        </td>
                                        <td className="px-6 py-4">{team.members}</td>
                                        <td className="px-6 py-4">{team.miles.toFixed(1)} miles</td>
                                        <td className="px-6 py-4 text-right">
                                            <button
                                                className="px-4 py-2 rounded-md bg-blue-500 text-white hover:bg-blue-600 transition-colors"
                                            >
                                                Follow
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    <div className="flex justify-between items-center mt-4">
                        <div className="text-sm text-gray-700">
                            Showing <span className="font-medium">1</span> to <span className="font-medium">{teamsPerPage}</span> of <span className="font-medium">{teamsToFollow.length}</span> results
                        </div>
                        <div className="flex gap-1">
                            <button className="px-3 py-1 border rounded-md bg-gray-100 hover:bg-gray-200">Previous</button>
                            <button className="px-3 py-1 border rounded-md bg-gray-100 hover:bg-gray-200">Next</button>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
