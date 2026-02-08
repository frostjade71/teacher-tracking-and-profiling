<?php
// app/actions/logout_post.php

audit_log('LOGOUT', 'user', current_user()['id'] ?? null);
logout_user();
redirect('login');
