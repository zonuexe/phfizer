# Phfizer - PHP File Analyzer

> [!WARNING]
> This tool is in the early stages of development and only the PSR-1 checker has been implemented.
> All features and interfaces are subject to change without notice.

## How to use

```php
% ./bin/phfizer analyze -- src/ tests/ | awk '$2 != "" {print $0}'
tests/Analyzer/data/mixed-sideeffects/mixed-function.php	MIXED_SIDE_EFFECTS
```
