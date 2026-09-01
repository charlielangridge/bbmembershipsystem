# Target MySQL 8.4 with an explicit legacy converter

The replacement schema targets MySQL 8.4 in CI, local Sail, and Laravel Cloud. The unknown legacy production engine and schema are not treated as the new application's persistence contract; compatibility analysis and a repeatable, reconciled converter will bridge the source snapshot to the new schema once production facts are available.
