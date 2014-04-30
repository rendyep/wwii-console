<?php

namespace WWII\Console\Runnable\MasterCuti;

class MasterCuti extends \WWII\Console\AbstractConsole
{
    public function run()
    {
        $start = new \DateTime();

        $this->generateMasterCuti();
        $this->generatePerpanjanganCuti();
        $this->regenerateMasterCuti();

        $end = new \DateTime();
        $diff = $end->diff($start);

        $this->displayMessage('Process start : ' . $start->format('d-m-Y H:i:s'));
        $this->displayMessage('Proess end    : ' . $end->format('d-m-Y H:i:s'));
        $this->displayMessage('Time elapsed  : ' . $end->diff($start)->format('%h hours %i minutes %s seconds'));
    }

    protected function generateMasterCuti()
    {
        $today = new \DateTime();

        $lastYear = clone($today);
        $lastYear->sub(new \DateInterval('P1Y'));

        $this->displayMessage('Preparing GenerateMasterCuti...');
        $rsMasterKaryawan = $this->databaseManager->prepare("SELECT t_PALM_PersonnelFileMst.fCode,"
            . " t_PALM_PersonnelFileMst.fName, t_PALM_PersonnelFileMst.fInDate, t_BMSM_DeptMst.fDeptName"
            . " FROM t_PALM_PersonnelFileMst"
            . " LEFT JOIN t_BMSM_DeptMst ON t_PALM_PersonnelFileMst.fDeptCode = t_BMSM_DeptMst.fDeptCode"
            . " WHERE fCode LIKE '0%' AND fInDate <= :lastYear AND fDFlag = 0"
            . " ORDER BY fInDate ASC, fCode ASC");
        $rsMasterKaryawan->bindParam(':lastYear', $lastYear->format('Y-m-d'));
        $rsMasterKaryawan->execute();

        $masterKaryawanList = $rsMasterKaryawan->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($masterKaryawanList)) {
            $this->displayMessage('No MasterKaryawan found.');
        } else {
            $this->prepareProgressBar(count($masterKaryawanList));
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
                $this->displayMessage('MasterCuti to generate: '.  count($scheduledEntityInsertions) . ' item(s)');
                $this->displayMessage('Generating MasterCuti...');
                $this->entityManager->flush();
            } else {
                $this->displayMessage('No new MasterCuti generated.');
            }
        }

        $this->displayMessage('GenerateMasterCuti completed!' . PHP_EOL);
        sleep(1);
    }

    protected function generatePerpanjanganCuti()
    {
        $now = new \DateTime();

        $this->displayMessage('Preparing GeneratePerpanjanganCuti...');
        $masterCutiList = $this->entityManager->createQueryBuilder()
            ->select('masterCuti')
            ->from('WWII\Domain\Hrd\Cuti\MasterCuti', 'masterCuti')
            ->where('masterCuti.tanggalKadaluarsa = :tanggalKadaluarsa')
            ->setParameter('tanggalKadaluarsa', $now->format('Y-m-d'))
            ->getQuery()->getResult();

        if (empty($masterCutiList)) {
            $this->displayMessage('No expired MasterCuti found.');
        } else {
            $this->prepareProgressBar(count($masterCutiList));
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
                $this->displayMessage('No new PerpanjanganCuti generated.');
            } else {
                $this->displayMessage('Saving to database...');
                $this->entityManager->flush();
            }
        }

        $this->displayMessage('GeneratePerpanjanganCuti completed!' . PHP_EOL);
        sleep(1);
    }

    protected function regenerateMasterCuti()
    {
        $now = new \DateTime();

        $this->displayMessage('Preparing RegeneratePerpanjanganCuti...');
        $masterCutiList = $this->entityManager->createQueryBuilder()
            ->select('masterCuti')
            ->from('WWII\Domain\Hrd\Cuti\MasterCuti', 'masterCuti')
            ->where('masterCuti.tanggalKadaluarsa = :tanggalKadaluarsa')
            ->setParameter('tanggalKadaluarsa', $now->format('Y-m-d'))
            ->getQuery()->getResult();

        if (empty($masterCutiList)) {
            $this->displayMessage('No expired MasterCuti found.');
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
                $this->displayMessage('No new MasterCuti regenerated.');
            } else {
                $this->displayMessage('Saving to database...');
                $this->entityManager->flush();
            }
        }

        $this->displayMessage('RegeneratePerpanjanganCuti completed!' . PHP_EOL);
        sleep(1);
    }

    private function isEmployeeActive($nik)
    {
        $rsEmployee = $this->databaseManager->prepare("SELECT count(t_PALM_PersonnelFileMst.fCode)"
            . " FROM t_PALM_PersonnelFileMst"
            . " WHERE t_PALM_PersonnelFileMst.fCode = :nik"
            . " AND t_PALM_PersonnelFileMst.fDFlag = 0");

        $rsEmployee->bindParam(":nik", $nik);
        $rsEmployee->execute();

        return count($rsEmployee->fetchColumn()) === 1;
    }
}
