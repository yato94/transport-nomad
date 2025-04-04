<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Team Invitation') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">
                        {{ __('You have been invited to join the :team team!', ['team' => $invitation->team->name]) }}
                    </h3>

                    <div class="mt-4 text-sm text-gray-600">
                        <p class="mb-4">
                            {{ __('You are currently a member of the :team team.', ['team' => $currentTeam->name]) }}
                        </p>
                        <p class="mb-4 font-bold text-red-600">
                            {{ __('If you accept this invitation, you will be removed from your current team and added to the :team team.', ['team' => $invitation->team->name]) }}
                        </p>
                        <p>
                            {{ __('Are you sure you want to accept this invitation?') }}
                        </p>
                    </div>

                    <div class="mt-6 flex items-center">
                        <form method="POST" action="{{ route('team-invitations.accept-with-confirmation.post', $invitation) }}">
                            @csrf
                            <x-button>
                                {{ __('Accept Invitation') }}
                            </x-button>
                        </form>

                        <form method="POST" action="{{ route('team-invitations.decline.post', $invitation) }}" class="ml-3">
                            @csrf
                            <x-button class="bg-red-500 hover:bg-red-700">
                                {{ __('Decline Invitation') }}
                            </x-button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
