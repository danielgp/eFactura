<?php

/*
 * Copyright (c) 2024, Daniel Popiniuc and its licensors.
 *
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Eclipse Public License v1.0
 * which accompanies this distribution, and is available at
 * http://www.eclipse.org/legal/epl-v10.html
 *
 * Contributors:
 *    Daniel Popiniuc
 */

$strCurrentLog = ini_get('error_log');
ini_set('error_log', substr($strCurrentLog, 0, - 4)
    . '_' . date('Y-m-d')
    . (array_key_exists('REMOTE_ADDR', $_SERVER) ? '_' . $_SERVER['REMOTE_ADDR'] : '')
    . '_eFacturaInterface.log');

require_once '../vendor/autoload.php';

$app = new \danielgp\efactura\ClassElectronicInvoiceUserInterface();

$app->setHtmlHeader();
$app->setUserInterface();
$app->setActionToDo();
$app->setHtmlFooter();
