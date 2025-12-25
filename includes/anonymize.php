<?php
/**
 * Data Anonymization Helper
 * Implements session-based anonymization for Primary Keys and Foreign Keys
 * to prevent exposing database structure and IDs
 */

/**
 * Generate a unique code for an ID
 * @param int $id The database ID to anonymize
 * @param string $prefix Prefix for the code (e.g., 'EXP', 'VEH')
 * @return string Anonymized code
 */
function anonymizeId($id, $prefix = 'EXP') {
    // Initialize session array if not exists
    if (!isset($_SESSION['id_map'])) {
        $_SESSION['id_map'] = [];
    }
    if (!isset($_SESSION['code_map'])) {
        $_SESSION['code_map'] = [];
    }
    
    // Check if ID already has a code
    $key = $prefix . '_' . $id;
    if (isset($_SESSION['id_map'][$key])) {
        return $_SESSION['id_map'][$key];
    }
    
    // Generate new unique code
    $code = $prefix . '-' . strtoupper(substr(md5($id . time() . rand()), 0, 8));
    
    // Store bidirectional mapping
    $_SESSION['id_map'][$key] = $code;
    $_SESSION['code_map'][$code] = $id;
    
    return $code;
}

/**
 * Decode an anonymized code back to the original ID
 * @param string $code The anonymized code
 * @return int|null The original ID or null if not found
 */
function deanonymizeCode($code) {
    if (!isset($_SESSION['code_map'])) {
        return null;
    }
    
    return $_SESSION['code_map'][$code] ?? null;
}

/**
 * Clear all anonymization mappings (useful for logout)
 */
function clearAnonymization() {
    unset($_SESSION['id_map']);
    unset($_SESSION['code_map']);
}

/**
 * Anonymize a URL parameter
 * @param string $url Base URL
 * @param string $paramName Parameter name (default: 'code')
 * @param int $id The ID to anonymize
 * @param string $prefix Prefix for anonymization
 * @return string Complete URL with anonymized parameter
 */
function buildAnonymizedUrl($url, $id, $paramName = 'code', $prefix = 'EXP') {
    $code = anonymizeId($id, $prefix);
    $separator = strpos($url, '?') !== false ? '&' : '?';
    return $url . $separator . $paramName . '=' . urlencode($code);
}
