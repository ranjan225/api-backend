<?php

declare(strict_types=1);

namespace Reconmap\Controllers\Reports;

use Psr\Http\Message\ServerRequestInterface;
use Reconmap\Controllers\Controller;
use Reconmap\Repositories\ReportRepository;

class GetReportsController extends Controller
{

	public function __invoke(ServerRequestInterface $request): array
	{
		$repository = new ReportRepository($this->db);
		$reports = $repository->findAll();

		return $reports;
	}
}
