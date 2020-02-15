<?php

namespace Drupal\pokeapi\Service;

class PaginationService {

  public function getPagination($totalPerPage, $allNodes, $currentPage) {
    $totalNodes = count($allNodes);
    $nbPage = ceil($totalNodes / $totalPerPage);

    return [
      'totalPerPage' => $totalPerPage,
      'totalNodes' => $totalNodes,
      'nbPage' => $nbPage,
      'currentPage' => $currentPage,
    ];
  }

  public function getCurrentPage() {
    if (isset($_GET['page']) && !empty($_GET['page'])) {
      return $_GET['page'];
    }
    else {
      return 1;
    }
  }

}
