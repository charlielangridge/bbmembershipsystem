---
paths:
  - 'app/MemberVisibility.php|app/Models/MemberProfile.php|app/Policies/MemberProfilePolicy.php|app/Http/Controllers/Member*|database/**/MemberProfile*|database/migrations/*member_profiles*|routes/web.php|resources/js/pages/members/**|tests/Feature/MemberDirectoryPrivacyTest.php'
---

# Members Feature

## Default member profiles and photos to member-only
Member profile and photo visibility default to members-only; public access requires the explicit public enum value. Only verified members may cross the members-only boundary. Serve stored profile photos from the private local disk through the authorized members.photo route, return 404 for hidden/missing records, and do not use Gravatar or expose account email in member props.
