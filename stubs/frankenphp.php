<?php

// Signatures of the FrankenPHP and frankenphp-websocket functions, for static analysis only.

const WEBSOCKET_AUTO = 0;
const WEBSOCKET_TEXT = 1;
const WEBSOCKET_BINARY = 2;
const WEBSOCKET_ERROR_NONE = 0;
const WEBSOCKET_ERROR_NOT_FOUND = 1;
const WEBSOCKET_ERROR_NOT_OPEN = 2;
const WEBSOCKET_ERROR_INVALID_ARGUMENT = 3;
const WEBSOCKET_ERROR_WRITE_FAILED = 4;
const WEBSOCKET_ERROR_BUS_FAILED = 5;
const WEBSOCKET_ERROR_TIMEOUT = 6;
const CLUSTER_ERROR_NONE = 0;
const CLUSTER_ERROR_NOT_JOINED = 1;
const CLUSTER_ERROR_INVALID_ARGUMENT = 2;
const CLUSTER_ERROR_BUS_FAILED = 3;
const CLUSTER_ERROR_TIMEOUT = 4;
const CLUSTER_ERROR_NO_RECEIVER = 5;

function frankenphp_handle_request(callable $callback): bool {}

function websocket_send(string $connection, string $data, int $type = 0): bool {}

function websocket_send_to_tag(string $tag, string $data, int $type = 0): int {}

function websocket_broadcast(string $data, int $type = 0): int {}

function websocket_ping(string $connection, string $payload = ''): bool {}

function websocket_close(string $connection, int $code = 1000, string $reason = ''): bool {}

function websocket_is_open(string $connection): bool {}

function websocket_instance(string $connection): string {}

/** @return list<string> */
function websocket_connections(): array {}

/** @return list<string> */
function websocket_connections_with_tag(string $tag): array {}

function websocket_set_attribute(string $connection, string $name, string $value): bool {}

function websocket_remove_attribute(string $connection, string $name): bool {}

/** @return array<string, string> */
function websocket_attributes(string $connection): array {}

function websocket_tag(string $connection, string $tag): bool {}

function websocket_untag(string $connection, string $tag): bool {}

/** @return list<string> */
function websocket_tags(string $connection): array {}

function websocket_last_error(): int {}

function cluster_self(): string {}

/** @return list<string> */
function cluster_members(): array {}

/** @return array<string, array{role: 'server'|'cli', receives: bool}> */
function cluster_member_info(): array {}

function cluster_send(string $instance, string $data): bool {}

function cluster_publish(string $data): int {}

function cluster_request(string $instance, string $data, int $timeout = 0): string {}

/** @param callable(array{event: 'message', from: string, data: string, request: bool}): ?string $handler */
function cluster_receive(callable $handler, int $timeout = 0): bool {}

function cluster_last_error(): int {}
