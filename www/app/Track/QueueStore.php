<?php

/**
 * QueueStore
 * Custom implementation of the JQStore interface
 * {@see QueueManager} for actual usage.
 */
namespace SCUpload\Track;

use SCUpload\SCUpload;
use JQJob;
use JQStore;
use JQManagedJob;
use JQStore_JobIsLockedException;
use JQStore_JobNotFoundException;

class QueueStore implements JQStore {

	/**
	 * @var SCUpload
	 */
	protected $app;

	/**
	 * @var PDO
	 */
	protected $db;

	/**
	 * @param SCUpload $app
	 */
	public function __construct(SCUpload $app) {
		$this->app = $app;
	}

	/**
	 * Return or create the PDO connection
	 * @return PDO
	 */
	protected function db() {
		if(!isset($this->db)) {
			$config = $this->app->app_config['credentials']['queue'];
			$this->db = new PDO(
				$config['dsn'],
				$config['username'],
				$config['password']
			);
		}
		return $this->db;
	}

	/**
	 * @return string
	 */
	protected function sqlTable() {
		return $this->app->app_config['credentials']['queue']['table'];
	}

	/**
	 * Shorthand for a simple select query...
	 * @return string
	 */
	protected function selectSql($where = null, $fields = '*', $order = null, $limit = null) {
		$sql = sprintf(
			'SELECT %s FROM "%s"',
			$fields,
			$this->sqlTable()
		);
		if(isset($where)) {
			$sql .= ' WHERE ' . $where;
		}
		if(isset($order)) {
			$sql .= ' ORDER BY ' . $order;
		}
		if(isset($limit)) {
			$sql .= ' LIMIT ' . $limit;
		}
		return $sql;
	}

	/**
	 * Restore a JQManagedJob from a database row
	 * @throws JQStore_JobNotFoundException if not found
	 */
	protected function restore($row) {
		if(!$row) {
			throw new JQStore_JobNotFoundException();
		}
		$job = new JQManagedJob($this);
		$job->fromArray(unserialize($row['object']));
		return $job;
	}

    /**
     * {@inheritdoc}
     */
	public function enqueue(JQJob $inputJob) {
		$job = new JQManagedJob($this, $inputJob);
		if($job->getStatus() === JQManagedJob::STATUS_UNQUEUED) {
			$job->setStatus(JQManagedJob::STATUS_QUEUED);
		}
		$db = $this->db();
		$sql = sprintf(
			'INSERT INTO "%s" (id, state, object) VALUES (\'%s\', \'%s\', \'%s\')',
			$this->sqlTable(),
			$db->quote($job->getJobId()),
			$db->quote($job->getStatus()),
			serialize($job->toArray())
		);
		$this->db()->exec($sql);
		return $job;
	}

	/**
	 * @return JQManagedJob
	 */
	public function next($queueName = null) {
		$select = sprintf(
			'state = \'%s\'',
			JQManagedJob::STATUS_QUEUED
		);
		$sql = $this->selectSql($select, 'sequence', 1);
		$result = $this->db()->query($sql);
		$row = $result->fetch(PDO::FETCH_ASSOC);
		$this->addMutex($row['id']);
		return $this->restore($row);
	}

	/**
	 * @return boolean
	 */
	public function function existsJobForCoalesceId($coalesceId) {
		$db = $this->db();
		$sql = $this->selectSql(sprintf(
			'"coalesce_id" = %s"',
			$db->quote($coalesceId)
		), 'COUNT(*)')
		return (int) $db->query($sql)->fetchColumn() > 0;
	}

	/**
	 * @param string $fields (like *, COUNT(*), etc)
	 * @param string $queueName
	 * @param string $status
	 * @return \PDOStatement
	 */
	protected function getMany($fields, $queueName = null, $status = null) {
		$db = $this->db();
		$where = null;
		if(isset($status)) {
			$where = sprintf('state = %s',
				$db->quote($status)
			);
		}
		$sql = $this->selectSql($where, $fields, 'sequence');
		return $db->query($sql);
	}

	/**
	 * {@inheritdoc}
	 */
	public function count($queueName = null, $status = null) {
		return $this->getMany('COUNT(*)', $queueName, $status)->fetchColumn();
	}

	/**
	 * {@inheritdoc}
	 */
	public function jobs($queueName = null, $status = null) {
		$rows = $this->getMany('*', $queueName, $status)->fetchAll(PDO::FETCH_ASSOC);
		$jobs = array();
		foreach($rows as $row) {
			$jobs[] = $this->restore($row);
		}
		return $jobs;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get($jobId) {
		$sql = $this->selectSql('id = '.$db->quote($jobId));
		return $this->restore(
			$this->db()->query($sql)->fetch()
		);
	}

	/**
	 * Set a mutex on a particular job ID to stop
	 * other instances running it
	 * @return void
	 */
	protected function addMutex($jobId) {
		$db = $this->db();
		$db->exec(sprintf(
			'UPDATE "%s" SET mutex = 1 WHERE id = %s',
			$this->sqlTable(),
			$db->quote($jobId)
		));
	}

	/**
	 * {@inheritdoc}
	 */
	public function getWithMutex($jobId) {
		$db = $this->db();
		$sqlTable = $this->sqlTable();
		$sql = $this->selectSql('id = ' . $db->quote($jobId));
		$result = $db->query($sql)->fetch();
		if(!$result) {
			throw new JQStore_JobNotFoundException();
		}
		if(!empty($result['mutex'])) {
			throw new JQStore_JobIsLockedException();
		}
		$this->addMutex($jobId);
		return $this->restore($result);
	}

	/**
	 * {@inheritdoc}
	 * @return void
	 */
	public function clearMutex($jobId) {
		$sql = sprintf(
			'UPDATE "%s" SET mutex = 0 WHERE id = \'%s\'',
			$this->sqlTable(),
			$jobId
		);
		// end mutex
		$this->db()->exec($sql);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getByCoalesceId($coalesceId) {
		$sql = $this->selectSql('coalesce_id = ' . $db->quote($coalesceId));
		return $this->restore($this->db()->query($sql)->fetch());
	}

	/**
	 * {@inheritdoc}
	 */
	public function save(JQManagedJob $job) {
		$db = $this->db();
		$sql = sprintf(
			'UPDATE "%s" SET "status" = %s, "object" = %s WHERE "id" = %s LIMIT 1',
			$this->sqlTable(),
			$db->quote($job->getStatus()),
			serialize($job->toArray())
			$db->quote($job->getJobId()),
		);
		$db->exec($sql);
		return $job;

	}

	/**
	 * {@inheritdoc}
	 */
	public function delete(JQManagedJob $job) {
		$db = $this->db();
		$sql = sprintf(
			'DELETE FROM "%s" WHERE id = %s LIMIT 1',
			$this->sqlTable(),
			$db->quote($job->getJobId())
		);
		$db->exec($sql);
		return $job;
	}

	/**
	 * {@inheritdoc}
	 */
	public function statusDidChange(JQManagedJob $mJob, $oldStatus, $message) {
		// nothing for now
	}

	/**
	 * {@inheritdoc}
	 */
	public function abort() {
		// nothing for now
	}

	/**
	 * {@inheritdoc}
	 */
	public function detectHungJobs() {
		// nothing for now
	}

}
