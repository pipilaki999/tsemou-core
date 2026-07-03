<?php if (!defined('ABSPATH')) exit; ?>
<?php
$status_labels = ['active'=>'🟢 Active','monitoring'=>'🟡 Monitoring','critical'=>'🔴 Critical','archived'=>'⚫ Archived'];
$status_label = $status_labels[$data['status']] ?? '🟢 Active';
$call_sign = $data['call_sign'] ?: 'SET-CALLSIGN';

$proofs = class_exists('\\TSEMOU\\Modules\\ProofEngine\\Proof_Engine') ? \TSEMOU\Modules\ProofEngine\Proof_Engine::get_proofs_for_file($post->ID) : [];
$real_proof_count = count($proofs);

$connected_companies = class_exists('\\TSEMOU\\Modules\\CompanyEngine\\Company_Engine') ? \TSEMOU\Modules\CompanyEngine\Company_Engine::get_connected_companies($post->ID) : [];
$connected_company_ids = class_exists('\\TSEMOU\\Modules\\CompanyEngine\\Company_Engine') ? \TSEMOU\Modules\CompanyEngine\Company_Engine::get_connected_company_ids($post->ID) : [];
$all_companies = class_exists('\\TSEMOU\\Modules\\CompanyEngine\\Company_Engine') ? \TSEMOU\Modules\CompanyEngine\Company_Engine::get_existing_companies() : [];
$company_count = count($connected_companies);
?>
<div class="tsemou-file-os">
    <header class="tsemou-file-top">
        <div class="tsemou-file-brand">
            <span class="tsemou-file-kicker">TSEMOU FILE</span>
            <strong><?php echo esc_html($call_sign); ?></strong>
            <em><?php echo esc_html($status_label); ?></em>
        </div>
        <div class="tsemou-file-title-block">
            <h2><?php echo esc_html(get_the_title($post)); ?></h2>
            <p><?php echo esc_html($data['question'] ?: 'Add the central question for this file.'); ?></p>
        </div>
        <div class="tsemou-file-actions">
            <a class="tsemou-action-button" href="<?php echo esc_url(admin_url('post-new.php?post_type=tsemou_proof')); ?>">+ Proof</a>
            <button type="button">Reveal Connections</button>
        </div>
    </header>
    <section class="tsemou-file-metrics">
        <div><strong><?php echo esc_html($real_proof_count); ?></strong><span>Proof Objects</span></div>
        <div><strong><?php echo esc_html($company_count); ?></strong><span>Companies</span></div>
        <label><input type="number" name="tsemou_signals_count" value="<?php echo esc_attr($data['signals_count']); ?>"><span>Signals</span></label>
        <label><input type="number" name="tsemou_updates_count" value="<?php echo esc_attr($data['updates_count']); ?>"><span>Updates</span></label>
    </section>
    <div class="tsemou-file-layout">
        <nav class="tsemou-file-nav">
            <div class="nav-group-title">FILE</div>
            <button type="button" class="active" data-panel="identity">Identity</button>
            <button type="button" data-panel="summary">Summary</button>
            <button type="button" data-panel="what">What Happened</button>
            <button type="button" data-panel="why">Why It Matters</button>
            <div class="nav-group-title">KNOWLEDGE</div>
            <button type="button" data-panel="connections">Connections</button>
            <button type="button" data-panel="proofs">Proofs</button>
            <button type="button" data-panel="companies">Companies</button>
            <div class="nav-group-title">CITIZEN</div>
            <button type="button" data-panel="citizen">Your Impact</button>
            <button type="button" data-panel="timeline">Timeline</button>
        </nav>
        <main class="tsemou-file-panel">
            <section class="tsemou-panel active" id="tsemou-panel-identity">
                <h3>Identity</h3>
                <div class="identity-grid">
                    <label>Call Sign<input type="text" name="tsemou_call_sign" value="<?php echo esc_attr($data['call_sign']); ?>" placeholder="AMZ-FIRE"></label>
                    <label>Status<select name="tsemou_status">
                        <option value="active" <?php selected($data['status'], 'active'); ?>>🟢 Active</option>
                        <option value="monitoring" <?php selected($data['status'], 'monitoring'); ?>>🟡 Monitoring</option>
                        <option value="critical" <?php selected($data['status'], 'critical'); ?>>🔴 Critical</option>
                        <option value="archived" <?php selected($data['status'], 'archived'); ?>>⚫ Archived</option>
                    </select></label>
                    <label>Last Update<input type="text" name="tsemou_last_update_note" value="<?php echo esc_attr($data['update_note']); ?>" placeholder="Updated 2 hours ago"></label>
                </div>
            </section>
            <section class="tsemou-panel" id="tsemou-panel-summary">
                <h3>Summary</h3>
                <label>The Question<textarea name="tsemou_question" rows="3"><?php echo esc_textarea($data['question']); ?></textarea></label>
                <label>Executive Summary<textarea name="tsemou_executive_summary" rows="5"><?php echo esc_textarea($data['summary']); ?></textarea></label>
            </section>
            <section class="tsemou-panel" id="tsemou-panel-what">
                <h3>What Happened</h3>
                <textarea name="tsemou_what_happened" rows="8"><?php echo esc_textarea($data['what_happened']); ?></textarea>
            </section>
            <section class="tsemou-panel" id="tsemou-panel-why">
                <h3>Why It Matters</h3>
                <textarea name="tsemou_why_matters" rows="8"><?php echo esc_textarea($data['why_matters']); ?></textarea>
            </section>
            <section class="tsemou-panel" id="tsemou-panel-connections">
                <h3>Connections</h3>
                <div class="tsemou-module-placeholder">Graph Engine module placeholder. Current links: File ↔ Companies and File ↔ Proofs.</div>
            </section>
            <section class="tsemou-panel" id="tsemou-panel-proofs">
                <h3>Proofs</h3>
                <p><a class="tsemou-action-button" href="<?php echo esc_url(admin_url('post-new.php?post_type=tsemou_proof')); ?>">+ Add Proof Object</a></p>
                <?php if ($proofs): ?>
                    <div class="tsemou-proof-list">
                    <?php foreach ($proofs as $proof):
                        $source = get_post_meta($proof->ID, '_tsemou_proof_source', true);
                        $type = get_post_meta($proof->ID, '_tsemou_proof_type', true);
                        $rel = get_post_meta($proof->ID, '_tsemou_proof_reliability', true);
                    ?>
                        <div class="tsemou-proof-card">
                            <strong><?php echo esc_html($proof->post_title); ?></strong>
                            <span><?php echo esc_html($source ?: 'No source'); ?></span>
                            <em><?php echo esc_html(strtoupper($type ?: 'PROOF')); ?> • <?php echo esc_html(strtoupper($rel ?: 'MEDIUM')); ?></em>
                            <a href="<?php echo esc_url(get_edit_post_link($proof->ID)); ?>">Edit Proof</a>
                        </div>
                    <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="tsemou-module-placeholder">No Proof objects connected yet.</div>
                <?php endif; ?>
            </section>
            <section class="tsemou-panel" id="tsemou-panel-companies">
                <h3>Companies</h3>

                <label>Connect Existing Companies</label>
                <select name="tsemou_connected_companies[]" multiple size="8" class="tsemou-company-select">
                    <?php foreach ($all_companies as $company): ?>
                        <option value="<?php echo esc_attr($company->ID); ?>" <?php selected(in_array($company->ID, $connected_company_ids, true)); ?>>
                            <?php echo esc_html($company->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="tsemou-help">Hold Ctrl to select multiple existing companies, then Save. After saving, add Role, Status and Reason below.</p>

                <?php if ($connected_companies): ?>
                    <div class="tsemou-company-list tsemou-company-relationships">
                    <?php foreach ($connected_companies as $company):
                        $rel = $company_relationships[$company->ID] ?? ['role' => 'related', 'status' => 'under_watch', 'reason' => ''];
                    ?>
                        <div class="tsemou-company-card">
                            <strong><?php echo esc_html($company->post_title); ?></strong>
                            <?php
                            $global_status = get_post_meta($company->ID, '_tsemou_company_status', true) ?: 'under_watch';
                            $global_score = get_post_meta($company->ID, '_tsemou_company_score', true);
                            ?>
                            <span>Global Status: <?php echo esc_html($global_status); ?><?php echo $global_score !== '' ? ' • Score: ' . esc_html($global_score) . '/100' : ''; ?></span>

                            <label>Role in this File</label>
                            <select name="tsemou_company_relationships[<?php echo esc_attr($company->ID); ?>][role]">
                                <option value="target" <?php selected($rel['role'], 'target'); ?>>Target</option>
                                <option value="supplier" <?php selected($rel['role'], 'supplier'); ?>>Supplier</option>
                                <option value="investor" <?php selected($rel['role'], 'investor'); ?>>Investor</option>
                                <option value="brand" <?php selected($rel['role'], 'brand'); ?>>Brand</option>
                                <option value="related" <?php selected($rel['role'], 'related'); ?>>Related</option>
                                <option value="mentioned" <?php selected($rel['role'], 'mentioned'); ?>>Mentioned</option>
                            </select>

                            <label>TSEMOU Status</label>
                            <select name="tsemou_company_relationships[<?php echo esc_attr($company->ID); ?>][status]">
                                <option value="planet_ally" <?php selected($rel['status'], 'planet_ally'); ?>>🟢 Planet Ally</option>
                                <option value="better_company" <?php selected($rel['status'], 'better_company'); ?>>🟢 Better Company</option>
                                <option value="under_watch" <?php selected($rel['status'], 'under_watch'); ?>>🟡 Under Watch</option>
                                <option value="needs_change" <?php selected($rel['status'], 'needs_change'); ?>>🟠 Needs Change</option>
                                <option value="red_flag" <?php selected($rel['status'], 'red_flag'); ?>>🔴 Red Flag</option>
                                <option value="toxic" <?php selected($rel['status'], 'toxic'); ?>>🔴 Toxic</option>
                                <option value="blacklisted" <?php selected($rel['status'], 'blacklisted'); ?>>⚫ Blacklisted</option>
                            </select>

                            <label>Connection Reason</label>
                            <textarea name="tsemou_company_relationships[<?php echo esc_attr($company->ID); ?>][reason]" rows="3" placeholder="Why is this company connected to this File?"><?php echo esc_textarea($rel['reason']); ?></textarea>

                            <a href="<?php echo esc_url(get_edit_post_link($company->ID)); ?>">Open Company</a>
                        </div>
                    <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="tsemou-module-placeholder">No connected companies yet.</div>
                <?php endif; ?>
            </section>
            <section class="tsemou-panel" id="tsemou-panel-citizen">
                <h3>Your Impact</h3>
                <label>Your Impact<textarea name="tsemou_your_impact" rows="5"><?php echo esc_textarea($data['your_impact']); ?></textarea></label>
                <label>Better Choices<textarea name="tsemou_better_choices" rows="5"><?php echo esc_textarea($data['better_choices']); ?></textarea></label>
            </section>
            <section class="tsemou-panel" id="tsemou-panel-timeline">
                <h3>Timeline</h3><div class="tsemou-module-placeholder">Timeline Engine placeholder.</div>
            </section>
        </main>
    </div>
</div>
