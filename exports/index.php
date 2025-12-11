<?php
// Empêche le listing de dossier
header('HTTP/1.0 403 Forbidden');
exit('Accès interdit');
