# skalyana_reference.sql backup

This contains the full reference data dump (religions, castes, states, cities).

To restore it, copy it back to `database/sql/` and re-run the old SkalyanaReferenceSeeder.

It was moved here to avoid SQL syntax errors on MariaDB during `php artisan migrate --seed`.
