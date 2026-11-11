# Security Policy

## Supported Versions

The package follows semantic versioning. Security fixes are released for the latest
minor on each supported major. Supported: 1.x.

Runtime requirement: PHP >= 8.4.

## What the library trusts

It does no I/O. `Url`, `Uri` and `IpAddress` validate format; `isPublic()` is a range
check, not SSRF protection on its own: see the README before using it to decide what to fetch.

## Reporting a Vulnerability

Please report vulnerabilities privately to maxim.shamaev@gmail.com rather than
opening a public issue. You will get an answer within a week.
