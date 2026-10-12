#!/usr/bin/env python3
"""Validate downloaded GeoLite2 databases before they replace the live copies.

Usage: check_geolite.py [--max-age-days N] <GeoLite2-Country.mmdb> <GeoLite2-City.mmdb>

The application only trusts a local database younger than 60 days
(app/helpers/fcc_forever_routing.php), so a fresh download must be
well inside that window.
"""
import argparse
import os
import sys
import time

import maxminddb

EXPECTED = {
    'GeoLite2-Country.mmdb': ('GeoLite2-Country', 3 * 1024 * 1024),
    'GeoLite2-City.mmdb': ('GeoLite2-City', 30 * 1024 * 1024),
}
# Google Public DNS is stable and allocated to the United States in both editions.
PROBE_IP = '8.8.8.8'
PROBE_COUNTRY = 'US'


def check(path: str, max_age_days: int) -> list:
    errors = []
    name = os.path.basename(path)
    if name not in EXPECTED:
        return [f'{name}: unexpected file name']
    database_type, min_size = EXPECTED[name]
    size = os.path.getsize(path)
    if size < min_size:
        errors.append(f'{name}: file is too small ({size} bytes)')
    with maxminddb.open_database(path) as reader:
        meta = reader.metadata()
        if meta.database_type != database_type:
            errors.append(f'{name}: database type is {meta.database_type}')
        age_days = (time.time() - meta.build_epoch) / 86400
        if age_days > max_age_days:
            errors.append(f'{name}: build is {age_days:.1f} days old')
        record = reader.get(PROBE_IP) or {}
        iso = (record.get('country') or {}).get('iso_code')
        if iso != PROBE_COUNTRY:
            errors.append(f'{name}: probe lookup returned {iso!r}')
        if database_type == 'GeoLite2-City' and 'location' not in record:
            errors.append(f'{name}: probe lookup has no location data')
        print(f'{name}: {meta.database_type}, built {time.strftime("%Y-%m-%d", time.gmtime(meta.build_epoch))}, {size} bytes')
    return errors


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument('--max-age-days', type=int, default=14)
    parser.add_argument('files', nargs=2)
    args = parser.parse_args()
    names = sorted(os.path.basename(f) for f in args.files)
    if names != sorted(EXPECTED):
        print('Pass exactly GeoLite2-Country.mmdb and GeoLite2-City.mmdb.', file=sys.stderr)
        return 2
    errors = []
    for path in args.files:
        errors.extend(check(path, args.max_age_days))
    for error in errors:
        print(f'ERROR {error}', file=sys.stderr)
    return 1 if errors else 0


if __name__ == '__main__':
    sys.exit(main())
