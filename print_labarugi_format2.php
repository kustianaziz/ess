<style>
    .format2-table {
        width: 100%;
        border-collapse: collapse;
        border: 0;
        font-size: 10px;
    }
    .format2-table th,
    .format2-table td {
        border: 0;
        padding: 3px 6px;
        vertical-align: top;
    }
    .format2-table thead th {
        font-weight: bold;
    }
    .section-row td {
        padding-top: 8px;
        font-weight: bold;
    }
    .subtotal-row td {
        border-top: 1px solid #333;
        border-bottom: 1px solid #333;
        font-weight: bold;
    }
    .grand-total-row td {
        border-top: 1px solid #333;
        border-bottom: 1px solid #333;
        font-weight: bold;
    }
    .amount {
        text-align: right;
        white-space: nowrap;
    }
    .signature-table {
        width: 100%;
        margin-top: 12px;
        font-size: 10px;
    }
    .signature-table td {
        border: 0;
        text-align: center;
    }
    .signature-space {
        height: 42px;
    }
</style>

<page backtop="5mm" backbottom="5mm" backleft="5mm" backright="5mm">
	<page_header>
	</page_header>

<?php
$dt = new DateTime($sampai_tanggal);
$ind_months = [
    1 => 'JANUARI', 2 => 'PEBRUARI', 3 => 'MARET', 4 => 'APRIL', 5 => 'MEI', 6 => 'JUNI',
    7 => 'JULI', 8 => 'AGUSTUS', 9 => 'SEPTEMBER', 10 => 'OKTOBER', 11 => 'NOVEMBER', 12 => 'DESEMBER'
];
$str_date = $dt->format('d') . ' ' . $ind_months[(int)$dt->format('m')] . ' ' . $dt->format('Y');
$ind_months_title = [
    1 => 'Januari', 2 => 'Pebruari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
    7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];
$dt_ttd = new DateTime();
$str_ttd_date = $dt_ttd->format('d') . ' ' . $ind_months_title[(int)$dt_ttd->format('m')] . ' ' . $dt_ttd->format('Y');
$user_entitas = $this->session->userdata('entitas');
$format2_level = isset($format2_level) ? (int)$format2_level : 0;
$ttd_nama = isset($ttd_nama) ? trim($ttd_nama) : '';
$ttd_jabatan = isset($ttd_jabatan) && trim($ttd_jabatan) != '' ? trim($ttd_jabatan) : 'Rektor';
$signature_context = strtoupper($unit . ' ' . $this->session->userdata('data_identitas')['nama'] . ' ' . $this->session->userdata('entitas'));
$ttd_kota = 'Yogyakarta';
if(strpos($signature_context, 'YKEP') !== false) {
    $ttd_kota = 'Jakarta';
} else if(strpos($signature_context, 'UNJAYA') !== false) {
    $ttd_kota = 'Yogyakarta';
} else if(strpos($signature_context, 'UNJANI') !== false || strpos($signature_context, 'RSGM') !== false) {
    $ttd_kota = 'Cimahi';
}
?>

<h4 align="center" style="margin-top:0px;margin-bottom:2px;font-weight:bold;"><?=strtoupper($unit)?></h4>
<h4 align="center" style="margin-top:0px;margin-bottom:2px;font-weight:bold;">LAPORAN PENGHASILAN KOMPREHENSIF</h4>
<h4 align="center" style="margin-top:0px;margin-bottom:15px;font-weight:bold;">UNTUK PERIODE YANG BERAKHIR s.d <?=$str_date?></h4>

<table align="center" class="format2-table" cellpadding="0" cellspacing="0">
<thead>
	<tr>
		<th style="width:55%;text-align:left;">AKUN / URAIAN</th>
        <th style="width:15%;text-align:right;"><?=$col1_title?></th>
        <th style="width:15%;text-align:right;"><?=$col2_title?></th>
        <th style="width:15%;text-align:right;"><?=$col3_title?></th>
    </tr>
</thead>
<tbody>
<?php
$kategori_names = [
    4 => 'PENDAPATAN',
    5 => 'BEBAN'
];

foreach([4, 5] as $kat_id) {
    if(!isset($get_data_coa_kategori[$kat_id])) continue;
?>
	    <tr class="section-row">
        <td colspan="4"><b><?=$kategori_names[$kat_id]?></b></td>
    </tr>
    <?php
    $stack = [];
    foreach($get_data_coa_kategori[$kat_id] as $row_coa) {
        if(!empty($user_entitas) && $user_entitas != 'all') {
            if(!empty($row_coa['asal_entitas']) && $row_coa['asal_entitas'] != 'Umum' && $row_coa['asal_entitas'] != $user_entitas) {
                continue;
            }
        }

        $level = $row_coa['level_parent'];
        $level_number = $level + 1;
        $is_cutoff_detail = ($format2_level > 0 && $level_number == $format2_level);

        // Close stack items
        while (!empty($stack) && end($stack)['level_parent'] >= $level) {
            $parent = array_pop($stack);
            if(!empty($user_entitas) && $user_entitas != 'all') {
                if(!empty($parent['asal_entitas']) && $parent['asal_entitas'] != 'Umum' && $parent['asal_entitas'] != $user_entitas) {
                    continue;
                }
            }

            $p_c1 = isset($c1['saldocoa'][$parent['id']]) ? $c1['saldocoa'][$parent['id']] : 0;
            $p_c2 = isset($c2['saldocoa'][$parent['id']]) ? $c2['saldocoa'][$parent['id']] : 0;
            $p_c3 = isset($c3['saldocoa'][$parent['id']]) ? $c3['saldocoa'][$parent['id']] : 0;

            if($exclude_zero == 1 && $p_c3 <= 0) continue;
            ?>
	            <tr class="subtotal-row">
                <td>
                    <?php for($i=0; $i<$parent['level_parent']; $i++) { ?>&nbsp;&nbsp;&nbsp;&nbsp;<?php } ?>
                    <b>Jumlah <?=coa_print_name($parent['nama'])?></b>
                </td>
	                <td class="amount"><b><?=rupiah($p_c1)?></b></td>
	                <td class="amount"><b><?=rupiah($p_c2)?></b></td>
	                <td class="amount"><b><?=rupiah($p_c3)?></b></td>
            </tr>
            <?php
        }

        $v_c1 = isset($c1['saldocoa'][$row_coa['id']]) ? $c1['saldocoa'][$row_coa['id']] : 0;
        $v_c2 = isset($c2['saldocoa'][$row_coa['id']]) ? $c2['saldocoa'][$row_coa['id']] : 0;
        $v_c3 = isset($c3['saldocoa'][$row_coa['id']]) ? $c3['saldocoa'][$row_coa['id']] : 0;

        $display = 1;
        if($format2_level > 0 && $level_number > $format2_level) $display = 0;
        if($exclude_detail == 1 && $row_coa['tipe'] != 1) $display = 0;
        if($exclude_zero == 1 && $v_c3 <= 0) $display = 0;

        if($display == 1) {
            ?>
            <tr>
                <td>
                    <?php for($i=0; $i<$row_coa['level_parent']; $i++) { ?>&nbsp;&nbsp;&nbsp;&nbsp;<?php } ?>
                    <?php if($row_coa['tipe'] == 1 && !$is_cutoff_detail) { ?>
                        <b><?=coa_print_name($row_coa['nama'])?></b>
                    <?php } else { ?>
                        <?=coa_print_name($row_coa['nama'])?>
                    <?php } ?>
                </td>
	                <td class="amount">
	                    <?php if($row_coa['tipe'] != 1 || $is_cutoff_detail) { ?><?=rupiah($v_c1)?><?php } ?>
	                </td>
	                <td class="amount">
	                    <?php if($row_coa['tipe'] != 1 || $is_cutoff_detail) { ?><?=rupiah($v_c2)?><?php } ?>
	                </td>
	                <td class="amount">
	                    <?php if($row_coa['tipe'] != 1 || $is_cutoff_detail) { ?><?=rupiah($v_c3)?><?php } ?>
	                </td>
            </tr>
            <?php
            if($row_coa['tipe'] == 1 && !$is_cutoff_detail) {
                $stack[] = $row_coa;
            }
        }
    }

    // Close remaining stack
    while (!empty($stack)) {
        $parent = array_pop($stack);
        if(!empty($user_entitas) && $user_entitas != 'all') {
            if(!empty($parent['asal_entitas']) && $parent['asal_entitas'] != 'Umum' && $parent['asal_entitas'] != $user_entitas) {
                continue;
            }
        }

        $p_c1 = isset($c1['saldocoa'][$parent['id']]) ? $c1['saldocoa'][$parent['id']] : 0;
        $p_c2 = isset($c2['saldocoa'][$parent['id']]) ? $c2['saldocoa'][$parent['id']] : 0;
        $p_c3 = isset($c3['saldocoa'][$parent['id']]) ? $c3['saldocoa'][$parent['id']] : 0;

        if($exclude_zero == 1 && $p_c3 <= 0) continue;
        ?>
        <tr class="subtotal-row">
            <td>
                <?php for($i=0; $i<$parent['level_parent']; $i++) { ?>&nbsp;&nbsp;&nbsp;&nbsp;<?php } ?>
                <b>Jumlah <?=coa_print_name($parent['nama'])?></b>
            </td>
            <td class="amount"><b><?=rupiah($p_c1)?></b></td>
            <td class="amount"><b><?=rupiah($p_c2)?></b></td>
            <td class="amount"><b><?=rupiah($p_c3)?></b></td>
        </tr>
        <?php
    }
}

$net_c1 = ($c1['saldocoakategori'][4] ?? 0) - ($c1['saldocoakategori'][5] ?? 0);
$net_c2 = ($c2['saldocoakategori'][4] ?? 0) - ($c2['saldocoakategori'][5] ?? 0);
$net_c3 = ($c3['saldocoakategori'][4] ?? 0) - ($c3['saldocoakategori'][5] ?? 0);
?>
<tr class="grand-total-row">
    <td><b>Kenaikan (Penurunan) Aset Neto</b></td>
    <td class="amount"><b><?=rupiah($net_c1)?></b></td>
    <td class="amount"><b><?=rupiah($net_c2)?></b></td>
    <td class="amount"><b><?=rupiah($net_c3)?></b></td>
</tr>
</tbody>
</table>
<table class="signature-table" cellpadding="0" cellspacing="0">
    <tr>
        <td style="width:58%;"></td>
        <td style="width:42%;"><?=$ttd_kota?>, <?=$str_ttd_date?></td>
    </tr>
    <tr>
        <td></td>
        <td><?=htmlspecialchars($ttd_jabatan, ENT_QUOTES, 'UTF-8')?></td>
    </tr>
    <tr>
        <td></td>
        <td class="signature-space"></td>
    </tr>
    <tr>
        <td></td>
        <td><?=htmlspecialchars($ttd_nama, ENT_QUOTES, 'UTF-8')?></td>
    </tr>
</table>
</page>
