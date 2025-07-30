# Valkey usage

Valkey is an open source (BSD) high-performance key/value datastore that supports a variety of workloads such as caching, message queues, and can act as a primary database.

The following commands can be run in the CLI to interact with Valkey. To enter CLI run:

`valkey-cli`

### Utility Commands

List all keys matching a pattern.

`KEYS *`

Get server information and statistics.

`INFO`

Check if the server is running.

`PING`

Delete all keys in all databases

`FLUSHALL`

Check data type stored at a specific key (Possible types: string, list, set, zset, hash, stream)

`TYPE keyName`

### Key-Value Operations

Set a string key to a value.

`SET keyName "keyValue"`

Get the value of a key.

`GET keyName`

Delete one or more keys.

`DEL keyName1 keyName2`

### Stream Commands

Read entries from a stream oldest to newest (revers '-' and '+' to reverse order).

`XRANGE streamName - +`

Delete stream

`DEL streamName`

Remove all stream entries and keep stream key

`XTRIM streamName MAXLEN 0`

Delete specific entry

`XDEL streamName <entryId>`
