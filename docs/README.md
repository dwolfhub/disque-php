# disque-php

## Installation

Install disque-php via Composer:

```bash
$ composer require mariano/disque-php --no-dev
```

If you want to run its tests remove the `--no-dev` argument.

## Connecting

First you will need to create an instance of `Disque\Client`, specifying a list
of hosts and ports where different Disque nodes are installed:

```php
$client = new Disque\Client([
    '127.0.0.1:7711',
    '127.0.0.1:7712'
]);
```

If no host is specified, `127.0.0.1:7711` is assumed. Hosts can also be added
via the `addServer($host, $port)` method:

```php
$client = new Client();
$client->addServer('127.0.0.1', 7712);
```

At this point no connection is yet established. You force the connection via 
the `connect()` method. As recommended by Disque, the connection is done 
as follows:

* The list of hosts is used to pick a random server.
* A connection is attempted against the picked server. If it fails, another
random node is tried.
* If a connection is successfull, the `HELLO` command is issued against this
server. If this fails, another random node is tried.
* If no connection is established and there are no servers left, a
`Disque\Connection\Exception\ConnectionException` is thrown.

Example call:

```php
$result = $client->connect();
var_dump($result);
```

The above `connect()` call will return an output similar to the following:

```
[
    'version' => 1,
    'id' => "7eff078744b72d24d9ab71db1fb600c48cf7ec2f",
    'nodes' => [
        [
            'id' => "7eff078744b72d24d9ab71db1fb600c48cf7ec2f",
            'host' => "127.0.0.1",
            'port' => "7711",
            'version' => "1"
        ],
        [
            'id' => "d8f6333f5386bae67a216e0365ea09323eadc127",
            'host' => "127.0.0.1",
            'port' => "7712",
            'version' => "1"
        ],
    ]
]
```

### Using another connector

By default disque-php does not require any other packages or libraries. It has
its own connector to Disque, that is fast and focused. If you wish to instead
use another connector to handle the connection with Disque, you can specify
so via the `setConnectionImplementation()` method. For example, if you wish
to use [predis](https://github.com/nrk/predis) (maybe because you are already
using its PHP extension), you would first add predis to your Composer
requirements:

```bash
$ composer require predis/predis --no-dev
```

And then configure the connection implementation class:

```php
$client->setConnectionImplementation(\Disque\Connection\Predis::class);
```

## Commands

Currently all Disque commands are implemented, and can be executed via the
`Disque\Client` class. Once you have established a connection, you can run
any of the following commands.

### ACKJOB

Acknowledges the execution of one or more jobs via job IDs. Signature:

```php
ackJob(string... $ids): int
```

Arguments:

* `string... $ids`: Each job ID as an argument

Return value:

* `int`: The number of jobs acknowledged

Example call:

```php
$jobCount = $client->ackJob('jobid1', 'jobid2');
```

### ADDJOB

Adds a job to the specified queue. Signature:

```php
addJob(string $queue, string $payload, array $options = []): string
```

Arguments:

* `$queue`: The name of the queue where to create the job. If no queue with
that name exists, it will be ceated automatically. Queues are also automatically
removed when they hold no pending jobs.
* `$payload`: Payload of the job. This is usually a JSON encoded set of arguments,
but you can specify whatever string you want.
* `$options`: Set of options, amongst:
  * `timeout`: an `int`, which specifies the timeout in milliseconds for the
    job. See [Disque's API](https://github.com/antirez/disque#api).
  * `replicate`: an`int`, to specify the number of nodes the job should be
    replicated to.
  * `delay`: an `int`, to specify the number of seconds that should elapse 
    before the job is queued by any server.
  * `retry`: an `int`, to specify the period (in seconds) after which, if the
    job is not acknowledged, the job is put again into the queue for delivery.
    See [Disque's API](https://github.com/antirez/disque#api).
  * `ttl`: an `int`, which is the maximum job life in seconds.
  * `maxlen`: an `int`, to specify that if there are alreayd these many
    jobs queued in the given queue, then this new job is refused.
  * `async`: a `bool`, if `true`, tells the server to let the command return
    ASAP and replicate the job to the other nodes in background. See 
    [Disque's API](https://github.com/antirez/disque#api).

Return value:

* `string`: the job ID

Example call:

```php
$jobId = $client->addJob('queue', json_encode(['name' => 'Mariano']));
var_dump($jobId);
```

### DELJOB

Completely delete a job from a specific node. Signature:

```php
delJob(string... $ids): int
```

Arguments:

* `string... $ids`: Each job ID as an argument

Return value:

* `int`: The number of jobs removed

Example call:

```php
$jobCount = $client->delJob('jobid1', 'jobid2');
```

### DEQUEUE

Remove the given jobs from the queue. Signature:

```php
dequeueJob(string... $ids): int
```

Arguments:

* `string... $ids`: Each job ID as an argument

Return value:

* `int`: The number of jobs dequeued

Example call:

```php
$jobCount = $client->dequeueJob('jobid1', 'jobid2');
```

### ENQUEUE

Queue the given jobs, if not already queued. Signature:

```php
enqueueJob(string... $ids): int
```

Arguments:

* `string... $ids`: Each job ID as an argument

Return value:

* `int`: The number of jobs enqueued

Example call:

```php
$jobCount = $client->enqueueJob('jobid1', 'jobid2');
```

### FASTACK

Acknowledges the execution of one or more jobs via job IDs, using a faster
approach than `ACKJOB`. See [Disque's API](https://github.com/antirez/disque#api)
to understand the difference with `ACKJOB` and decide when to use which.
Signature:

```php
fastAck(string... $ids): int
```

Arguments:

* `string... $ids`: Each job ID as an argument

Return value:

* `int`: The number of jobs acknowledged

Example call:

```php
$jobCount = $client->fastAck('jobid1', 'jobid2');
```

### GETJOB

Gets a job (or several jobs if the option `count` is used) from the specified 
queue, and acknowledges the job (so it is no longer pending). Signature:

```php
getJob(string... $queues, array $options = []): array
```

Arguments:

* `$queues`: The set of queues from where to fetch jobs.
* `$options`: Set of options, amongst:
  * `timeout`: an `int`, which specifies the timeout in milliseconds to wait
    for jobs. If no jobs are available and this `timeout` expired, then no
    jobs are returned.
  * `count`: an `int`, to specify the number of jobs you wish to obtain.

Return value:

* `array`: A set of jobs, where each job is an indexed array with:
  * `queue`: a `string`, that indicates from which queue this job came from.
  * `id`: a `string`, which is the job ID.
  * `body`: a `string`, which is the payload of the job.

Example call:

```php
$jobs = $client->getJob('queue1', 'queue2', [
    'timeout' => 3000
]);
if (empty($jobs)) {
    die('NO JOBS!');
}

$job = $jobs[0];
echo "QUEUE: {$job['queue']}\n";
echo "ID: {$job['id']}\n";
var_dump(json_decode($job['body'], true));
```

### HELLO

Returns information from the connected node. You would normally not need to
use this, as it is using during the connection handshake. Signature:

```php
hello(): array
```

Arguments:

* None

Return value:

* `array`: Indexed array with:
  * `version`: a `string`, which indicates the `HELLO` format version.
  * `id`: a `string`, which is the ID of the Disque node we are connected to.
  * `nodes`: an `array`, which is a set of nodes, and where each node is an
    indexed array with:
    * `id`: a `string`, which is the ID of this Disque node.
    * `host`: a `string`, which is the host where this node is listening.
    * `port`: an `int`, which is the port where this node is listening.
    * `version`: a `string`, which indicates the `HELLO` format version.

Example call:

```php
$hello = $client->hello();
var_dump($hello);
```

### INFO

Get generic server information and statistics. You would normally not need to
use this. Signature:

```php
info(): string
```

Arguments:

* None

Return value:

* `string`: A big string with information about the connected node.

Example call:

```php
$info = $client->info();
echo $info;
```

### QLEN

The length of the queue, that is, the number of jobs available in the given
queue. Signature:

```php
qlen(string $queue): int
```

Arguments:

* `$queue`: Queue from which to get the number of jobs available.

Return value:

* `int`: Queue length.

Example call:

```php
$count = $client->qlen('queue');
var_dump($hello);
```

### QPEEK

Gets the given number of jobs from the given queue without consuming them (so
they will still be pending in the queue). Signature:

```php
qpeek(string $queue, int $count): array
```

Arguments:

* `$queue`: The queue from where to look for jobs.
* `count`: an `int`, to specify the number of jobs you wish to obtain. If this
    number is negative, then it will get these number of newest jobs.

Return value:

* `array`: A set of jobs, where each job is an indexed array with:
  * `id`: a `string`, which is the job ID.
  * `body`: a `string`, which is the payload of the job.

Example call:

```php
$jobs = $client->qpeek('queue', 1);
if (empty($jobs)) {
    die('NO JOBS!');
}

$job = $jobs[0];
echo "ID: {$job['id']}\n";
var_dump(json_decode($job['body'], true));
```

### SHOW

Get information about the given job. Signature:

```php
show(string $id): array
```

Arguments:

* `string $id`: job ID

Return value:

* `array`: An indexed array with information about the job, including (but not
    limited to) `queue`, `state`, `ttl`, `delay`, `retry`, `body`,
    `nodes-delivered`, `nodes-confirmed`. See [Disque's API](https://github.com/antirez/disque#api).

Example call:

```php
$details = $client->show('jobid1');
var_dump($details);
```
