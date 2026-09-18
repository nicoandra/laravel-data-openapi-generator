
Temporary quick prompt

```
as a software developer, please read the document docs/REPOSITORY_NOTES.md and then execute the first task of the backlog (agents/tasks/01.backlog) .

Once the task is completed, run the quality checks (make format, make test) and report completion.

if no errors were found, move the task to the "done" folder agents/tasks/05.done

if you are stuck ask me and I'll try to help.
```

# Signed-string protocol decision

The signed-string v1 protocol intentionally has no nonce and no replay store. Clients may retry the same token; validity depends on the HMAC signature and TTL, together with the existing canonical JSON, strict Base64url, key, size, and timestamp guarantees. Do not reintroduce nonce generation, replay tracking, or clock abstractions. Use Carbon directly for current time and CarbonInterval for parseable TTL overrides.
