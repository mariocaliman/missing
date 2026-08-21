-- Cleanup leading punctuation prefixes in imported case titles/details
-- Safe to run multiple times.

UPDATE news
SET title = TRIM(
    CASE
        WHEN title LIKE '# : %' THEN SUBSTRING(title, 5)
        WHEN title LIKE '#: %' THEN SUBSTRING(title, 4)
        WHEN title LIKE ': %' THEN SUBSTRING(title, 3)
        WHEN title LIKE '- %' THEN SUBSTRING(title, 3)
        ELSE title
    END
)
WHERE title LIKE '# : %'
   OR title LIKE '#: %'
   OR title LIKE ': %'
   OR title LIKE '- %';

UPDATE news
SET details = TRIM(
    CASE
        WHEN details LIKE '# : %' THEN SUBSTRING(details, 5)
        WHEN details LIKE '#: %' THEN SUBSTRING(details, 4)
        WHEN details LIKE ': %' THEN SUBSTRING(details, 3)
        WHEN details LIKE '- %' THEN SUBSTRING(details, 3)
        ELSE details
    END
)
WHERE details LIKE '# : %'
   OR details LIKE '#: %'
   OR details LIKE ': %'
   OR details LIKE '- %';

UPDATE news
SET details = REPLACE(details, '\n#: ', '\n');

UPDATE news
SET details = REPLACE(details, '\n# : ', '\n');

UPDATE news
SET details = REPLACE(details, '\n: ', '\n');

UPDATE news
SET details = REPLACE(details, 'Name: #: ', 'Name: ');

UPDATE news
SET details = REPLACE(details, 'Name: # : ', 'Name: ');

UPDATE news
SET details = REPLACE(details, 'Name: : ', 'Name: ');
