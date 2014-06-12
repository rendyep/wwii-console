<?php

namespace WWII\Console\Runnable\Cuti;

class CutiController extends \WWII\Console\AbstractConsole
{
    public function defaultAction($args = null) {
        system('php wwii.php cuti --help');
    }

    public function generateMasterCutiAction($args = null)
    {
        $this->displayMessage(PHP_EOL . 'Executing master cuti generator...');

        $today = new \DateTime();
        $lastYear = clone($today);
        $lastYear->sub(new \DateInterval('P1Y'));

        $this->displayMessage('Fetching data from ERP...');
        $rsMasterKaryawan = $this->databaseManager->prepare(
            "SELECT t_PALM_PersonnelFileMst.fCode,"
            . " t_PALM_PersonnelFileMst.fName, t_PALM_PersonnelFileMst.fInDate, t_BMSM_DeptMst.fDeptName"
            . " FROM t_PALM_PersonnelFileMst"
            . " LEFT JOIN t_BMSM_DeptMst ON t_PALM_PersonnelFileMst.fDeptCode = t_BMSM_DeptMst.fDeptCode"
            . " WHERE fCode LIKE '0%' AND fInDate <= :lastYear AND fDFlag = 0"
            . " ORDER BY fInDate ASC, fCode ASC"
        );
        $rsMasterKaryawan->bindParam(':lastYear', $lastYear->format('Y-m-d'));
        $rsMasterKaryawan->execute();

        $masterKaryawanList = $rsMasterKaryawan->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($masterKaryawanList)) {
            $this->displayMessage('No master karyawan found.');
        } else {
            $this->prepareProgressBar(count($masterKaryawanList), 'Analyzing');
            for ($i = 0; $i < count($masterKaryawanList); $i++) {
                $masterKaryawan = $masterKaryawanList[$i];

                $masterCuti = $this->entityManager->createQueryBuilder()
                    ->select('masterCuti')
                    ->from('WWII\Domain\Hrd\Cuti\MasterCuti', 'masterCuti')
                    ->where('masterCuti.nik = :nik')
                    ->setParameter('nik', $masterKaryawan['fCode'])
                    ->getQuery()->getResult();

                if (empty($masterCuti)) {
                    $masterCuti = new \WWII\Domain\Hrd\Cuti\MasterCuti();
                    $masterCuti->setNik($masterKaryawan['fCode']);
                    $masterCuti->setNamaKaryawan($masterKaryawan['fName']);
                    $masterCuti->setDepartemen($masterKaryawan['fDeptName']);

                    $tanggalMasukKerja = new \DateTime($masterKaryawan['fInDate']);
                    $masterCuti->setTanggalMasuk($tanggalMasukKerja);
                    $tanggalBerlaku = clone($tanggalMasukKerja);
                    while ($tanggalBerlaku < $lastYear) {
                        $tanggalBerlaku->add(new \DateInterval('P1Y'));
                    }

                    $tanggalKadaluarsa = clone($tanggalBerlaku);
                    $tanggalKadaluarsa->add(new \DateInterval('P1Y'));

                    $masterCuti->setTanggalBerlaku($tanggalBerlaku);
                    $masterCuti->setTanggalKadaluarsa($tanggalKadaluarsa);

                    $this->entityManager->persist($masterCuti);
                }

                $this->incrementProgressBar($i);
            }
            $this->closeProgressBar();

            $scheduledEntityInsertions = $this->entityManager->getUnitOfWork()->getScheduledEntityInsertions();
            if (!empty($scheduledEntityInsertions)) {
                $this->displayMessage('Master cuti to generate: '.  count($scheduledEntityInsertions) . ' item(s)');
                $this->displayMessage('Processing...');
                $this->entityManager->flush();
            } else {
                $this->displayMessage('No new master cuti generated.');
            }
        }

        $this->displayMessage('Done!');
    }

    public function generatePerpanjanganCutiAction(array $args = null)
    {
        $this->displayMessage(PHP_EOL . 'Executing perpanjangan cuti generator...');
        $now = new \DateTime();

        $this->displayMessage('Fetching data from master cuti...');
        $masterCutiList = $this->entityManager->createQueryBuilder()
            ->select('masterCuti')
            ->from('WWII\Domain\Hrd\Cuti\MasterCuti', 'masterCuti')
            ->where('masterCuti.tanggalKadaluarsa = :tanggalKadaluarsa')
            ->setParameter('tanggalKadaluarsa', $now->format('Y-m-d'))
            ->getQuery()->getResult();

        if (empty($masterCutiList)) {
            $this->displayMessage('No expired master cuti found.');
        } else {
            $this->prepareProgressBar(count($masterCutiList), 'Analyzing');
            for ($i = 0; $i < count($masterCutiList); $i++) {
                $masterCuti = $masterCutiList[$i];

                if ($masterCuti->getPerpanjanganCuti() !== null) {
                    $this->incrementProgressBar($i);
                    continue;
                }

                $tanggalKadaluarsa = clone($now);
                $tanggalKadaluarsa->add(new \DateInterval('P3M'));

                $perpanjanganCuti = new \WWII\Domain\Hrd\Cuti\PerpanjanganCuti();
                $perpanjanganCuti->setTanggalKadaluarsa($tanggalKadaluarsa);
                $perpanjanganCuti->setMasterCuti($masterCuti);

                $this->entityManager->persist($perpanjanganCuti);
                $this->incrementProgressBar($i);
            }
            $this->closeProgressBar();

            $scheduledEntityInsertions = $this->entityManager->getUnitOfWork()->getScheduledEntityInsertions();
            if (empty($scheduledEntityInsertions)) {
                $this->displayMessage('No new perpanjangan cuti generated.');
            } else {
                $this->displayMessage(
                    'Perpanjangan cuti to generate: '
                    . count($scheduledEntityInsertions) . ' item(s)'
                );
                $this->displayMessage('Processing...');
                $this->entityManager->flush();
            }
        }

        $this->displayMessage('Done!');
    }

    public function regenerateMasterCutiAction(array $args = null)
    {
        $this->displayMessage(PHP_EOL . 'Executing regenerate master cuti...');
        $now = new \DateTime();

        $this->displayMessage('Fetching data from master cuti...');
        $masterCutiList = $this->entityManager->createQueryBuilder()
            ->select('masterCuti')
            ->from('WWII\Domain\Hrd\Cuti\MasterCuti', 'masterCuti')
            ->leftJoin('masterCuti.child', 'child')
            ->where('masterCuti.tanggalKadaluarsa = :tanggalKadaluarsa')
            ->andWhere('child IS NULL')
            ->setParameter('tanggalKadaluarsa', $now->format('Y-m-d'))
            ->getQuery()->getResult();

        if (empty($masterCutiList)) {
            $this->displayMessage('No expired master cuti found.');
        } else {
            $this->prepareProgressBar(count($masterCutiList));
            for ($i = 0; $i < count($masterCutiList); $i++) {
                $masterCuti = $masterCutiList[$i];

                if (!$this->isEmployeeActive($masterCuti->getNik())) {
                    $this->incrementProgressBar($i);
                    continue;
                }

                $tanggalKadaluarsa = clone($now);
                $tanggalKadaluarsa->add(new \DateInterval('P1Y'));

                $masterCutiChild = new \WWII\Domain\Hrd\Cuti\MasterCuti();
                $masterCutiChild->setNik($masterCuti->getNik());
                $masterCutiChild->setNamaKaryawan($masterCuti->getNamaKaryawan());
                $masterCutiChild->setDepartemen($masterCuti->getDepartemen());
                $masterCutiChild->setTanggalBerlaku(new \DateTime());
                $masterCutiChild->setTanggalKadaluarsa($tanggalKadaluarsa);
                $masterCutiChild->setTanggalMasuk($masterCuti->getTanggalMasuk());
                $masterCutiChild->setParent($masterCuti);

                $this->entityManager->persist($masterCutiChild);
                $this->incrementProgressBar($i);
            }
            $this->closeProgressBar();

            $scheduledEntityInsertions = $this->entityManager->getUnitOfWork()->getScheduledEntityInsertions();
            if (empty($scheduledEntityInsertions)) {
                $this->displayMessage('No new master cuti regenerated.');
            } else {
                $this->displayMessage('Regenerating master cuti...');
                $this->entityManager->flush();
            }
        }

        $this->displayMessage('Done!');
    }

    public function generateAllAction(array $args = null)
    {
        $this->generateMasterCutiAction($args);
        $this->generatePerpanjanganCutiAction($args);
        $this->regenerateMasterCutiAction($args);
    }

    private function isEmployeeActive($nik)
    {
        $rsEmployee = $this->databaseManager->prepare(
            "SELECT count(t_PALM_PersonnelFileMst.fCode)"
            . " FROM t_PALM_PersonnelFileMst"
            . " WHERE t_PALM_PersonnelFileMst.fCode = :nik"
            . " AND t_PALM_PersonnelFileMst.fDFlag = 0"
        );

        $rsEmployee->bindParam(":nik", $nik);
        $rsEmployee->execute();

        return count($rsEmployee->fetchColumn()) === 1;
    }
}
