# Phfizer - PHP File Analyzer

> [!WARNING]
> This tool is in the early stages of development and only the PSR-1 checker has been implemented.
> All features and interfaces are subject to change without notice.

## How to use

```php
% ./bin/phfizer analyze -- src/ tests/ | awk '$2 != "" {print $0}'
tests/Analyzer/data/mixed-sideeffects/mixed-function.php	MIXED_SIDE_EFFECTS
```

## Parallel processing

Files are analyzed in parallel by splitting them across worker subprocesses, one
batch per CPU thread. The main process spawns the workers, which each emit their
results as ND-JSON, and the main process aggregates them. Output is identical to a
sequential run.

| Option | Description |
| --- | --- |
| `--jobs=N`, `-j N` | Number of parallel jobs (`0` = auto-detect CPU threads, the default). |
| `--no-parallel` | Analyze sequentially in a single process. |

```php
% ./bin/phfizer analyze -j 8 -- src/ tests/
% ./bin/phfizer analyze --no-parallel -- src/ tests/
```

## Caching

Analysis results are cached by the hash of each file's contents, so re-running
over unchanged files skips parsing and returns instantly. The cache key depends
only on the contents, so identical or renamed files share an entry and editing a
file invalidates only that entry.

| Option | Description |
| --- | --- |
| `--cache-dir=DIR` | Directory to store the cache (defaults to `phfizer/` under the system temp directory). |
| `--no-cache` | Disable the cache for this run. |

```php
% ./bin/phfizer analyze --cache-dir=.phfizer-cache -- src/ tests/
% ./bin/phfizer analyze --no-cache -- src/ tests/
```
