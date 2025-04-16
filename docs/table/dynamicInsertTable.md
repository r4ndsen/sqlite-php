# Dynamic Insert Table

If you need to insert heterogeneous data into a table, `r4ndsen/sqlite` will assist you with that.

The final table will contain all columns pushed.

In case a column is pushed upper and then lower cased (or vise versa) the first occurring case will be used.

```php
$sqlite = new r4ndsen\SQLite;
$dataTable = $sqlite->data->getDynamicInsertTable();
$dataTable
    ->push(['id' => 1, 'title' => 'Moby Dick'])
    ->push(['ID ' => 2, 'link' => 'https://github.com'])
    ->push(['title' => 'IT', 'link' => 'https://www.stephenking.com'])
;
```

| id | title | link |
| --- | --- | --- |
| 1 | Moby Dick | |
| 2 | | https://github.com |
| | IT | https://www.stephenking.com |
