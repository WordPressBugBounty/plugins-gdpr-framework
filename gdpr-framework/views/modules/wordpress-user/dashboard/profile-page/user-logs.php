<h2>
    <?= _x('GDPR User logs', 'gdpr-framework'); ?>
</h2>
<hr>
<?php if (count($userlogData)): ?> 
<div class="userlog-scroll">
    <table class="gdpr-user-logs">
        <th><?= _x('S.no', 'gdpr-framework'); ?></th>
        <th><?= _x('User ID', 'gdpr-framework'); ?></th>
        <th><?= _x('User logs', 'gdpr-framework'); ?></th>
        <th><?= _x('Updated date', 'gdpr-framework'); ?></th>
        <?php $x=1;foreach ($userlogData as $item):
            // Security fix (SECURITY-AUDIT.md Finding 5 / PR review Finding 3):
            // decode this DB-sourced string with gdpr_decode_user_log(), which
            // prefers JSON (how new rows are written) and falls back to the
            // object-blocking legacy serialized decoder. maybe_unserialize()
            // would still instantiate a serialized object.
            $userlog_data = gdpr_decode_user_log($item->userlog);
            unset($userlog_data['user_pass']);
            unset($userlog_data['user_activation_key']);
            unset($userlog_data['user_status']);
            $userid=$userlog_data['ID'];
            unset($userlog_data['ID']);
            ?>
            <tr>
                <td>            
                    <?php echo $x++;?>
                </td>
                <td>
                    <?php echo esc_html($userid);?>
                </td>
                <td>
                    <ul>
                        <?php
                        if ($userlog_data) {
                            foreach ($userlog_data as $key => $detail) {
                                $key = print_r($key, true);
                                $detail = print_r($detail, true);
                                // Security fix (SECURITY-AUDIT.md Finding 3): this
                                // snapshots a user's own nickname/name/bio fields
                                // (gdpr-framework.php: my_profile_update()) and
                                // was previously echoed unescaped into every
                                // administrator's Edit Profile screen -- escape it.
                                echo "<li><strong>" . esc_html($key) . ":</strong>" . esc_html($detail) . "</li>";
                            }
                        }
                        echo "</br>";?>
                    </ul>
                </td>
                <td>
                <?php echo esc_html($item->updated_at);?>

                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    </div>
<?php else: ?>
    <p><?= _x('No User Logs', 'gdpr-framework'); ?>.</p>
<?php endif; ?>