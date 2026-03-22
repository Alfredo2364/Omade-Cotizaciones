<?php
function renderPagination($currentPage, $totalPages, $queryParams = []) {
    if ($totalPages <= 1) return '';

    // Build the query string base
    $queryString = '';
    foreach ($queryParams as $key => $value) {
        if ($key !== 'page' && $value !== '') {
            $queryString .= '&' . urlencode($key) . '=' . urlencode($value);
        }
    }
    
    // Chunk logic: Show 20 pages at a time
    $chunkSize = 20;
    $currentChunk = ceil($currentPage / $chunkSize);
    $startPage = ($currentChunk - 1) * $chunkSize + 1;
    $endPage = min($startPage + $chunkSize - 1, $totalPages);

    $html = '<div class="pagination-container">';

    // Jump Backward 20 (<<)
    if ($startPage > 1) {
        $prevChunkPage = $startPage - 1; // Go to last page of previous chunk
        $html .= "<a href='?page=$prevChunkPage$queryString' class='page-box' title='Anteriores $chunkSize'>&laquo;</a>";
    }

    // Previous Button (<)
    if ($currentPage > 1) {
        $prev = $currentPage - 1;
        $html .= "<a href='?page=$prev$queryString' class='page-box prev-next'>&lsaquo;</a>";
    } else {
        $html .= "<span class='page-box prev-next disabled'>&lsaquo;</span>";
    }

    // Render Page Numbers in current chunk
    for ($i = $startPage; $i <= $endPage; $i++) {
        $active = ($i == $currentPage) ? 'active' : '';
        $html .= "<a href='?page=$i$queryString' class='page-box $active'>$i</a>";
    }

    // Next Button (>)
    if ($currentPage < $totalPages) {
        $next = $currentPage + 1;
        $html .= "<a href='?page=$next$queryString' class='page-box prev-next'>&rsaquo;</a>";
    } else {
        $html .= "<span class='page-box prev-next disabled'>&rsaquo;</span>";
    }

    // Jump Forward 20 (>>)
    if ($endPage < $totalPages) {
        $nextChunkPage = $endPage + 1; // Go to first page of next chunk
        $html .= "<a href='?page=$nextChunkPage$queryString' class='page-box' title='Siguientes $chunkSize'>&raquo;</a>";
    }

    $html .= '</div>';
    return $html;
}
?>
