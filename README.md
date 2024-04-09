# Phfizer - PHP File Analyzer

## How to use

```php
% ./bin/phfizer analyze -- src/ tests/ | awk '$2 != "" {print $0}'
tests/Analyzer/data/mixed-sideeffects/mixed-function.php	MIXED_SIDE_EFFECTS
```
