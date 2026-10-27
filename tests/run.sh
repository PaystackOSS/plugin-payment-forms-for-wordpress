#!/usr/bin/env bash
# Runs every tests/test-*.php; exits non-zero if any fail.
dir="$(cd "$(dirname "$0")" && pwd)"
status=0
for f in "$dir"/test-*.php; do
	echo "== $(basename "$f")"
	php "$f" || { echo "FAILED: $(basename "$f")"; status=1; }
done
exit $status
