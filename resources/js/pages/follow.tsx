import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { useState } from 'react';

// Define interfaces for the data structures
interface Person {
    id: number;
    name: string;
    progress: number;
    miles: number;
    isFollowing: boolean;
}

interface Team {
    id: number;
    name: string;
    progress: number;
    miles: number;
    isFollowing: boolean;
}

interface TeamStatistics {
    distance_total: number;
    distance_completed: number;
    distance_remaining: number;
    progress_percentage: number;
}

interface EventData {
    id: number;
    name: string;
    social_hashtags: string;
    description: string;
    start_date: string;
    end_date: string;
    total_points: string;
    registration_url: string;
    team_size: number;
    organization_id: number;
    created_at: string;
    updated_at: string;
    supported_modalities: number;
    event_type: string;
    open: boolean;
    template: number;
    logo: string;
    bibs_name: string;
    event_group: string;
    calendar_days: null | string;
    goals: string;
    logo_url: string;
}

interface TeamData {
    id: number;
    name: string;
    event_id: number;
    owner_id: number;
    public_profile: boolean;
    created_at: string;
    updated_at: string;
    settings: string;
    event: EventData;
}

interface TeamFollowing {
    id: number;
    follower_id: number;
    team_id: number;
    event_id: number;
    created_at: string;
    updated_at: string;
    statistics: TeamStatistics;
    team: TeamData;
}

interface TeamFollowingsResponse {
    current_page: number;
    data: TeamFollowing[];
    first_page_url: string;
    from: number;
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number;
}

interface UserFollowing {
    id: number;
    display_name: string;
    first_name: string;
    last_name: string;
    total_miles: number;
}

interface UserFollowingsResponse {
    current_page: number;
    data: UserFollowing[];
    first_page_url: string;
    from: number;
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number;
}

interface EventParticipant {
    id: number;
    display_name: string;
    first_name: string;
    last_name: string;
    public_profile: boolean;
    following_status_text: string;
    following_status: string;
}

interface EventParticipantsResponse {
    current_page: number;
    data: EventParticipant[];
    first_page_url: string;
    from: number;
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number;
}

// Define interface for team data from API
interface TeamToFollow {
    id: number;
    name: string;
    public_profile: boolean;
    settings: string;
    event_id: number;
    is_team_owner: boolean;
    membership_status: string | null;
}

interface TeamsToFollowResponse {
    current_page: number;
    data: TeamToFollow[];
    first_page_url: string;
    from: number;
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number;
}

interface FollowProps {
    teamFollowings?: {
        data: TeamFollowingsResponse;
    };
    userFollowings?: {
        data: UserFollowingsResponse;
    };
    users?: {
        data: EventParticipantsResponse;
    };
    teams?: {
        data: TeamsToFollowResponse;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Follow',
        href: route('follow'),
    },
];

export default function Dashboard({ teamFollowings, userFollowings, users, teams }: FollowProps) {
    const [peopleSearch, setPeopleSearch] = useState('');
    const [teamSearch, setTeamSearch] = useState('');
    const [peoplePerPage, setPeoplePerPage] = useState(5);
    const [teamsPerPage, setTeamsPerPage] = useState(5);

    // Extract teams and people from the API responses
    const followedTeams: Team[] = teamFollowings?.data?.map(following => ({
        id: following.team.id,
        name: following.team.name,
        progress: following.statistics.progress_percentage,
        miles: following.statistics.distance_completed,
        isFollowing: true
    })) || [];

    const followedPeople: Person[] = userFollowings?.data?.map(user => ({
        id: user.id,
        name: user.display_name,
        progress: 0,
        miles: user.total_miles,
        isFollowing: true
    })) || [];

    // Get users to follow from the provided data
    const usersToFollow = users?.data || [];

    // Get teams to follow from the provided data
    const teamsToFollow = teams?.data || [];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Follow" />
            <div className="flex flex-col gap-6 p-4">
                {/* People I Follow Section */}
                <div className="bg-white rounded-lg shadow-sm p-6">
                    <h2 className="text-xl font-semibold mb-4">People I Follow</h2>
                    {followedPeople.length > 0 ? (
                        <div className="space-y-4">
                            {followedPeople.map((person) => (
                                <div key={person.id} className="flex items-center justify-between border-b pb-4 last:border-b-0">
                                    <div className="flex items-center gap-3">
                                        <div className="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center text-gray-500">
                                            {person.name.charAt(0)}
                                        </div>
                                        <span className="font-medium">{person.name}</span>
                                    </div>

                                    <div className="flex-1 mx-6">
                                        <div className="w-full bg-gray-200 rounded-full h-2.5">
                                            <div
                                                className="bg-blue-600 h-2.5 rounded-full"
                                                style={{ width: `${person.progress}%` }}
                                            ></div>
                                        </div>
                                    </div>

                                    <div className="flex items-center gap-6">
                                        <span className="font-medium whitespace-nowrap">{person.miles.toFixed(1)} miles</span>

                                        <button
                                            className="px-3 py-2 rounded-md bg-red-500 text-white hover:bg-red-600 transition-colors"
                                        >
                                            Unfollow
                                        </button>
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <p className="text-gray-500">You are not following anyone.</p>
                    )}
                </div>

                {/* Teams I Follow Section */}
                <div className="bg-white rounded-lg shadow-sm p-6">
                    <h2 className="text-xl font-semibold mb-4">Teams I Follow</h2>
                    {followedTeams.length > 0 ? (
                        <div className="space-y-4">
                            {followedTeams.map((team) => (
                                <div key={team.id} className="flex items-center justify-between border-b pb-4 last:border-b-0">
                                    <div className="flex items-center gap-3">
                                        <div className="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center text-gray-500">
                                            {team.name.charAt(0)}
                                        </div>
                                        <span className="font-medium">{team.name}</span>
                                    </div>

                                    <div className="flex-1 mx-6">
                                        <div className="w-full bg-gray-200 rounded-full h-2.5">
                                            <div
                                                className="bg-green-600 h-2.5 rounded-full"
                                                style={{ width: `${team.progress}%` }}
                                            ></div>
                                        </div>
                                    </div>

                                    <div className="flex items-center gap-6">
                                        <span className="font-medium whitespace-nowrap">{team.miles.toFixed(1)} miles</span>
                                        <button
                                            className="px-3 py-2 rounded-md bg-red-500 text-white hover:bg-red-600 transition-colors"
                                        >
                                            Unfollow
                                        </button>
                                    </div>
                                </div>
                            ))}
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

                    {usersToFollow.length > 0 ? (
                        <div className="space-y-4">
                            {usersToFollow.map((person) => (
                                <div key={person.id} className="flex items-center justify-between border-b pb-4 last:border-b-0">
                                    <div className="flex items-center gap-3">
                                        <div className="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center text-gray-500">
                                            {person.display_name.charAt(0)}
                                        </div>
                                        <div>
                                            <span className="font-medium block">{person.display_name}</span>
                                            <span className="text-sm text-gray-500">{person.first_name} {person.last_name}</span>
                                        </div>
                                    </div>

                                    <div>
                                        <button
                                            className={`px-4 py-2 rounded-md ${
                                                person.following_status === 'following'
                                                    ? 'bg-green-500 hover:bg-green-600'
                                                    : person.following_status === 'request_to_follow_issued'
                                                        ? 'bg-yellow-500 hover:bg-yellow-600'
                                                        : 'bg-blue-500 hover:bg-blue-600'
                                            } text-white transition-colors`}
                                        >
                                            {person.following_status_text}
                                        </button>
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <p className="text-gray-500 py-4 text-center">No participants found.</p>
                    )}

                    {users?.data?.next_page_url && (
                        <div className="flex justify-between items-center mt-4">
                            <div className="text-sm text-gray-700">
                                Showing <span className="font-medium">{users.data.from}</span> to <span className="font-medium">{users.data.to}</span> results
                            </div>
                            <div className="flex gap-1">
                                {users.data.prev_page_url && (
                                    <a href={users.data.prev_page_url} className="px-3 py-1 border rounded-md bg-gray-100 hover:bg-gray-200">
                                        Previous
                                    </a>
                                )}
                                {users.data.next_page_url && (
                                    <a href={users.data.next_page_url} className="px-3 py-1 border rounded-md bg-gray-100 hover:bg-gray-200">
                                        Next
                                    </a>
                                )}
                            </div>
                        </div>
                    )}
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
                                    <th className="px-6 py-3 text-left">Status</th>
                                    <th className="px-6 py-3 text-right"></th>
                                </tr>
                            </thead>
                            <tbody>
                                {teamsToFollow.length > 0 ? (
                                    teamsToFollow.map((team) => (
                                        <tr key={team.id} className="border-b last:border-b-0">
                                            <td className="px-6 py-4">
                                                <div className="flex items-center gap-3">
                                                    <div className="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center text-gray-500">
                                                        {team.name.charAt(0)}
                                                    </div>
                                                    <span className="font-medium">{team.name}</span>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4">
                                                {team.membership_status ? (
                                                    <span className="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">
                                                        {team.membership_status}
                                                    </span>
                                                ) : (
                                                    <span className="px-2 py-1 bg-gray-100 text-gray-800 rounded-full text-xs">
                                                        Not a member
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-6 py-4 text-right">
                                                <button
                                                    className="px-4 py-2 rounded-md bg-blue-500 text-white hover:bg-blue-600 transition-colors"
                                                    disabled={team.membership_status === "Joined"}
                                                >
                                                    {team.membership_status === "Joined" ? "Joined" : "Follow"}
                                                </button>
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan={3} className="px-6 py-4 text-center text-gray-500">
                                            No teams found.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {teams?.data?.next_page_url && (
                        <div className="flex justify-between items-center mt-4">
                            <div className="text-sm text-gray-700">
                                Showing <span className="font-medium">{teams.data.from}</span> to <span className="font-medium">{teams.data.to}</span> results
                            </div>
                            <div className="flex gap-1">
                                {teams.data.prev_page_url && (
                                    <a href={teams.data.prev_page_url} className="px-3 py-1 border rounded-md bg-gray-100 hover:bg-gray-200">
                                        Previous
                                    </a>
                                )}
                                {teams.data.next_page_url && (
                                    <a href={teams.data.next_page_url} className="px-3 py-1 border rounded-md bg-gray-100 hover:bg-gray-200">
                                        Next
                                    </a>
                                )}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
