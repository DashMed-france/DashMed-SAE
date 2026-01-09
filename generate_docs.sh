#!/bin/bash
PROJECT_ROOT=$(pwd)
./vendor/bin/phpdoc -d "$PROJECT_ROOT/app" -t "$PROJECT_ROOT/docs/api" --title "DashMed Documentation" --force
echo "Documentation generated in docs/api"
