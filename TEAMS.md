# Single Team Membership Implementation

This document provides a comprehensive overview of the single team membership implementation in our Laravel application using Jetstream. The system has been customized to enforce a strict single-team policy, where users can only belong to one team at a time.

## Table of Contents

1. [Overview](#overview)
2. [Database Structure](#database-structure)
3. [Team Creation](#team-creation)
4. [Team Invitation Process](#team-invitation-process)
5. [Team Membership Management](#team-membership-management)
6. [User Interface Modifications](#user-interface-modifications)
7. [Key Files and Components](#key-files-and-components)

## Overview

Our application implements a strict single-team membership model, where:

- Each user can only belong to one team at a time
- When a user accepts an invitation to a new team, they are automatically removed from their current team
- Users cannot switch between teams; they can only be in one team at a time
- If a user leaves a team, a new personal team is automatically created for them
- The UI has been modified to hide any team-switching functionality

This approach ensures clear organizational boundaries and simplifies the user experience by eliminating the need to switch between teams.

## Database Structure

The team functionality relies on several database tables:

- `teams`: Stores team information (id, name, user_id of owner, personal_team flag)
- `team_user`: Junction table for team memberships (team_id, user_id, role)
- `team_invitations`: Stores pending invitations (id, team_id, email, role)
- `users`: Contains user information, including current_team_id

## Team Creation

### Initial Team Creation

When a user registers, a personal team is automatically created for them unless they are registering with an invitation:

1. The `CreateNewUser` action creates a new user record
2. If the user is not registering with an invitation, a personal team is created
3. The user is set as the owner of this team
4. The user's `current_team_id` is set to this team

### Creating Additional Teams

Users can create additional teams, but with restrictions:

1. The `TeamPolicy` enforces that users cannot create a new team if they already belong to a team
2. The `CreateTeam` action checks if the user already has a team before creating a new one
3. If a user attempts to create a team while already belonging to one, a validation error is thrown

## Team Invitation Process

### Sending Invitations

Team owners can invite users to their team:

1. The `InviteTeamMember` action creates a new invitation record
2. An email is sent to the invitee with a signed URL to accept or decline the invitation
3. Different email templates are used for existing users vs. new users
4. For existing users, the email includes both "Accept Invitation & Switch Teams" and "Decline Invitation" buttons

### Accepting Invitations

When a user clicks on the "Accept Invitation" link:

1. The `TeamInvitationController` handles the request
2. If the user is not logged in:
   - The system checks if the email already exists in the database
   - If the user exists, they are redirected to the login page with the invitation ID and action=accept
   - If the user doesn't exist, they are redirected to the registration page with the invitation ID
3. For logged-in users:
   - The system shows a confirmation page asking if they want to switch teams
   - If they confirm, the user is removed from all their current teams
   - The user is added to the invited team
   - The user's current team is switched to the new team
   - The invitation is deleted

### Declining Invitations

When a user clicks on the "Decline Invitation" link:

1. The `TeamInvitationController` handles the request
2. If the user is not logged in:
   - The system checks if the email already exists in the database
   - If the user exists, they are redirected to the login page with the invitation ID and action=decline
   - If the user doesn't exist, they are redirected to the registration page with the invitation ID
3. For logged-in users:
   - The system shows a confirmation page asking if they want to decline the invitation
   - If they confirm, the invitation is deleted without changing their team membership
   - The user remains in their current team

### Handling Invitations After Login

When a user logs in with an invitation ID:

1. The `HandleTeamInvitationAfterLogin` listener is triggered
2. It checks if there's an invitation ID and action parameter in the request
3. If the action is 'decline':
   - The invitation is deleted without changing team membership
   - A success message is shown to the user
4. If the action is 'accept' or not specified:
   - The user is removed from all their current teams
   - The user is added to the invited team
   - The user's current team is switched to the new team
   - The invitation is deleted

### Handling Invitations During Registration

When a user registers with an invitation ID:

1. The `CreateNewUser` action checks for an invitation ID and action parameter
2. If the action is 'decline':
   - The invitation is deleted without adding the user to the team
   - A personal team is created for the user
3. If the action is 'accept' or not specified:
   - The user is added to the invited team
   - The user's current team is switched to the new team
   - The invitation is deleted
   - No personal team is created for the user

## Team Membership Management

### Leaving a Team

When a user leaves a team:

1. The `removeFromTeam` method in the `HasSingleTeam` trait is called
2. The user is detached from the team
3. The user's current team is dissociated
4. If the user has no teams left:
   - A new personal team is created for the user
   - The user is added to this team as the owner
   - The user's current team is switched to this new team

### Removing a Team Member

When a team owner removes a member:

1. The `RemoveTeamMember` action is called
2. The user is removed from the team
3. If the user has no teams left:
   - A new personal team is created for the user
   - The user is added to this team as the owner
   - The user's current team is switched to this new team

## User Interface Modifications

The UI has been modified to enforce the single-team policy:

1. The "Switch Teams" option has been completely removed from the navigation menu
2. The team dropdown in the navigation bar only shows team management options
3. The mobile navigation menu also has the team switcher removed
4. The registration page hides the team name field when registering with an invitation

## Key Files and Components

### Models

- `app/Models/User.php`: User model with team relationships
- `app/Models/Team.php`: Team model
- `app/Models/TeamInvitation.php`: Team invitation model

### Traits

- `app/Traits/HasSingleTeam.php`: Contains the core team membership logic, including:
  - `allTeams()`: Returns all teams the user owns or belongs to
  - `removeFromTeam()`: Handles removing a user from a team and creating a personal team if needed
  - `isCurrentTeam()`: Checks if a team is the user's current team

### Actions

- `app/Actions/Fortify/CreateNewUser.php`: Handles user registration and team creation
- `app/Actions/Jetstream/CreateTeam.php`: Creates new teams with validation
- `app/Actions/Jetstream/InviteTeamMember.php`: Handles team invitations
- `app/Actions/Jetstream/AddTeamMember.php`: Adds users to teams
- `app/Actions/Jetstream/RemoveTeamMember.php`: Removes users from teams

### Controllers

- `app/Http/Controllers/TeamInvitationController.php`: Handles invitation acceptance and redirection

### Event Listeners

- `app/Listeners/HandleTeamInvitationAfterLogin.php`: Processes invitations after login

### Views

- `resources/views/navigation-menu.blade.php`: Modified to remove team switching UI
- `resources/views/auth/login.blade.php`: Modified to handle invitation and action parameters
- `resources/views/auth/register.blade.php`: Modified to handle invitation and action parameters
- `resources/views/teams/accept-invitation-confirmation.blade.php`: Confirmation page for accepting team invitations
- `resources/views/teams/decline-invitation-confirmation.blade.php`: Confirmation page for declining team invitations
- `resources/views/teams/team-member-manager.blade.php`: Modified to handle null membership cases
- `resources/views/emails/team-invitation-existing-user.blade.php`: Email template for invitations to existing users with accept and decline buttons

### Policies

- `app/Policies/TeamPolicy.php`: Enforces team creation restrictions

## Conclusion

This single-team membership implementation ensures that users can only belong to one team at a time, simplifying the user experience and enforcing clear organizational boundaries. The system handles all edge cases, such as team invitations, leaving teams, and team member removal, ensuring that users always have a team to work with.
