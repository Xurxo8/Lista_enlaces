# Consulta para recoller os hooks do frontend
SELECT id_hook, name, title
FROM `ps_hook` 
WHERE name LIKE "display%"
	AND name NOT LIKE "Admin%"
    AND name NOT LIKE "Debug%"
ORDER BY id_hook ASC;