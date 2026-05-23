#!/bin/sh
# Railway pre-deploy no-op wrapper
# Railway sometimes runs a pre-deploy command that expects the `railway` CLI.
# Make Railway run this script as the pre-deploy command to avoid build-time failures.

echo "predeploy: no-op"
exit 0
