<?php

namespace WWII\Console\Runnable\DailyPresence;

class DailyPresenceController extends \WWII\Console\AbstractConsole
{
    public function run(array $options = null, array $args = null)
    {
        if ($options['generateDailyPresence'] !== null) {
            $this->generateDailyPresence($options, $args);
        } else {
            system('wwii.bat daily-presence --help');
        }
    }

    public function generateDailyPresence(array $options = null, array $args = null) {
        $configManager = $this->serviceManager->getConfigManager();
        $dbConfig = $configManager->get('database');

        if (! isset($args['datetime']) || (isset($args['datetime']) && empty($args['datetime']))) {
            $dateTime = new \DateTime();
            $dateTime->sub(new \DateInterval('P1D'));
        } else {
            $dateTime = new \DateTime($args['datetime']);
        }

        $dateTimeNow = clone($dateTime);
        $dateTimeYesterday = clone($dateTime);
        $dateTimeYesterday = $dateTimeYesterday->sub(new \DateInterval('P1D'));
        $dateTimeTomorrow = clone($dateTime);
        $dateTimeTomorrow = $dateTimeTomorrow->add(new \DateInterval('P1D'));

        $this->displayMessage(PHP_EOL . "Getting personnel daily schedule ({$dateTime->format('Y-m-d')})...");
        $presences = $this->databaseManager->prepare("
            WITH
            MasterTurn AS (
                SELECT
                    t_AMSM_TurnMst.fTurnCode,
                    fTimeScheduledIn1 = CONVERT(
                        DATETIME,
                        CONVERT(
                            VARCHAR(2),
                            t_AMSM_TurnMst.fTurn1SH
                        )
                        + ':'
                        + CONVERT(
                            VARCHAR(2),
                            t_AMSM_TurnMst.fTurn1SM
                        )
                        + ':00'
                    ),
                    fTimeScheduledOut1 = CONVERT(
                        DATETIME,
                        CONVERT(
                            VARCHAR(2),
                            t_AMSM_TurnMst.fTurn1EH
                        )
                        + ':'
                        + CONVERT(
                            VARCHAR(2),
                            t_AMSM_TurnMst.fTurn1EM
                        )
                        + ':00'
                    ),
                    fTimeScheduledIn2 = CONVERT(
                        DATETIME,
                        CONVERT(
                            VARCHAR(2),
                            t_AMSM_TurnMst.fTurn2SH
                        )
                        + ':'
                        + CONVERT(
                            VARCHAR(2),
                            t_AMSM_TurnMst.fTurn2SM
                        )
                        + ':00'
                    ),
                    fTimeScheduledOut2 = CONVERT(
                        DATETIME,
                        CONVERT(
                            VARCHAR(2),
                            t_AMSM_TurnMst.fTurn2EH
                        )
                        + ':'
                        + CONVERT(
                            VARCHAR(2),
                            t_AMSM_TurnMst.fTurn2EM
                        )
                        + ':00'
                    ),
                    fTimeScheduledIn3 = CONVERT(
                        DATETIME,
                        CONVERT(
                            VARCHAR(2),
                            t_AMSM_TurnMst.fTurn3SH
                        )
                        + ':'
                        + CONVERT(
                            VARCHAR(2),
                            t_AMSM_TurnMst.fTurn3SM
                        )
                        + ':00'
                    ),
                    fTimeScheduledOut3 = CONVERT(
                        DATETIME,
                        CONVERT(
                            VARCHAR(2),
                            t_AMSM_TurnMst.fTurn3EH
                        )
                        + ':'
                        + CONVERT(
                            VARCHAR(2),
                            t_AMSM_TurnMst.fTurn3EM
                        )
                        + ':00'
                    ),
                    fTimeScheduledIn4 = CONVERT(
                        DATETIME,
                        CONVERT(
                            VARCHAR(2),
                            t_AMSM_TurnMst.fTurn4SH
                        )
                        + ':'
                        + CONVERT(
                            VARCHAR(2),
                            t_AMSM_TurnMst.fTurn4SM
                        )
                        + ':00'
                    ),
                    fTimeScheduledOut4 = CONVERT(
                        DATETIME,
                        CONVERT(
                            VARCHAR(2),
                            t_AMSM_TurnMst.fTurn4EH
                        )
                        + ':'
                        + CONVERT(
                            VARCHAR(2),
                            t_AMSM_TurnMst.fTurn4EM
                        )
                        + ':00'
                    ),
                    fTimeScheduledIn = CONVERT(
                        DATETIME,
                        CONVERT(
                            VARCHAR(2),
                            t_AMSM_TurnMst.fTurn1SH
                        )
                        + ':'
                        + CONVERT(
                            VARCHAR(2),
                            t_AMSM_TurnMst.fTurn1SM
                        )
                        + ':00'
                    ),
                    fTimeScheduledOut = CASE
                        WHEN
                            CONVERT(VARCHAR(2), t_AMSM_TurnMst.fTurn4EH) + ':'
                                + CONVERT(VARCHAR(2), t_AMSM_TurnMst.fTurn4EM) + ':00'
                            <> '0:0:00'
                        THEN
                            CONVERT(
                                DATETIME,
                                CONVERT(
                                    VARCHAR(2),
                                    t_AMSM_TurnMst.fTurn4EH
                                )
                                + ':'
                                + CONVERT(
                                    VARCHAR(2),
                                    t_AMSM_TurnMst.fTurn4EM
                                )
                                + ':00'
                            )
                        WHEN
                            CONVERT(VARCHAR(2), t_AMSM_TurnMst.fTurn3EH) + ':'
                                + CONVERT(VARCHAR(2), t_AMSM_TurnMst.fTurn3EM) + ':00'
                            <> '0:0:00'
                        THEN
                            CONVERT(
                                DATETIME,
                                CONVERT(
                                    VARCHAR(2),
                                    t_AMSM_TurnMst.fTurn3EH
                                )
                                + ':'
                                + CONVERT(
                                    VARCHAR(2),
                                    t_AMSM_TurnMst.fTurn3EM
                                )
                                + ':00'
                            )
                        WHEN
                            CONVERT(VARCHAR(2), t_AMSM_TurnMst.fTurn2EH) + ':'
                                + CONVERT(VARCHAR(2), t_AMSM_TurnMst.fTurn2EM) + ':00'
                            <> '0:0:00'
                        THEN
                            CONVERT(
                                DATETIME,
                                CONVERT(
                                    VARCHAR(2),
                                    t_AMSM_TurnMst.fTurn2EH
                                )
                                + ':'
                                + CONVERT(
                                    VARCHAR(2),
                                    t_AMSM_TurnMst.fTurn2EM
                                )
                                + ':00'
                            )
                        ELSE
                            CONVERT(
                                DATETIME,
                                CONVERT(
                                    VARCHAR(2),
                                    t_AMSM_TurnMst.fTurn1EH
                                )
                                + ':'
                                + CONVERT(
                                    VARCHAR(2),
                                    t_AMSM_TurnMst.fTurn1EM
                                )
                                + ':00'
                            )
                        END
                FROM
                    t_AMSM_TurnMst
            ),
            DailyTurn AS (
                SELECT
                    t_AMSD_MonthlyTurns.fCode,
                    fTurnCode = SUBSTRING(t_AMSD_MonthlyTurns.fTurns, {$dateTimeYesterday->format('d')}*4+1, 3)
                FROM
                    t_AMSD_MonthlyTurns
                WHERE
                    t_AMSD_MonthlyTurns.fYear = {$dateTimeNow->format('Y')}
                    AND t_AMSD_MonthlyTurns.fMonth = {$dateTimeNow->format('n')}
            )

            SELECT
                t_PALM_PersonnelFileMst.fCode,
                fDateTimeScheduledIn = CONVERT(DATETIME, '{$dateTimeNow->format('Y-m-d')} ' + MasterTurn.fTimeScheduledIn),
                fDateTimeScheduledOut = CASE
                    WHEN
                        MasterTurn.fTimeScheduledIn < MasterTurn.fTimeScheduledOut
                    THEN
                        CONVERT(DATETIME, '{$dateTimeNow->format('Y-m-d')} ' + MasterTurn.fTimeScheduledOut)
                    ELSE
                        CONVERT(DATETIME, '{$dateTimeTomorrow->format('Y-m-d')} ' + MasterTurn.fTimeScheduledOut)
                END
            FROM
                t_PALM_PersonnelFileMst
                LEFT JOIN DailyTurn ON DailyTurn.fCode = t_PALM_PersonnelFileMst.fCode
                LEFT JOIN MasterTurn ON MasterTurn.fTurnCode = DailyTurn.fTurnCode
            WHERE
                t_PALM_PersonnelFileMst.fDFlag = 0
                AND t_PALM_PersonnelFileMst.fInDate <= '{$dateTimeNow->format('Y-m-d')}'
            ORDER BY
                t_PALM_PersonnelFileMst.fCode ASC
        ");
        $presences->execute();
        $presences = $presences->fetchAll(\PDO::FETCH_ASSOC);
        $this->displayMessage("Done!");
        $this->displayMessage("Found " . count($presences) . " number(s) of active personnel.");

        $this->prepareProgressBar(count($presences));
        for ($i = 0; $i < count($presences); $i++) {
            $presence = $presences[$i];

            $cardRecord = $this->databaseManager->prepare("
                SELECT
                    t_AMSD_CardRecord.fCode,
                    fDateTimeUserIn = MIN(t_AMSD_CardRecord.fDateTime),
                    fDateTimeUserOut = CASE
                        WHEN
                            MAX(t_AMSD_CardRecord.fDateTime) > MIN(t_AMSD_CardRecord.fDateTime)
                        THEN
                            MAX(t_AMSD_CardRecord.fDateTime)
                        ELSE
                            NULL
                    END
                FROM
                    t_AMSD_CardRecord
                WHERE
                    t_AMSD_CardRecord.fCode = '{$presence['fCode']}'
                    AND t_AMSD_CardRecord.fDateTime >= DATEADD(HOUR, -3, '{$presence['fDateTimeScheduledIn']}')
                    AND t_AMSD_CardRecord.fDateTime <= DATEADD(HOUR, 3, '{$presence['fDateTimeScheduledOut']}')
                GROUP BY
                    t_AMSD_CardRecord.fCode
            ");
            $cardRecord->execute();
            $cardRecord = $cardRecord->fetch(\PDO::FETCH_ASSOC);

            if ($cardRecord['fDateTimeUserIn'] !== null || $cardRecord['fDateTimeUserOut'] !== null) {
                $status = 'P';
            } elseif ($presence['fDateTimeScheduledIn'] == null && $presence['fDateTimeScheduledOut'] == null) {
                if ($cardRecord['fDateTimeUserIn'] !== null || $cardRecord['fDateTimeUserOut'] !== null) {
                    $status = 'P';
                } else {
                    $status = 'H';
                }
            } else {
                $status = 'A';
            }

            $query = $this->databaseManager->prepare("
                SELECT
                    a_Personnel_CardRecord.fId
                FROM
                    a_Personnel_CardRecord
                WHERE
                    a_Personnel_CardRecord.fCode = '{$presence['fCode']}'
                    AND a_Personnel_CardRecord.fDateTime = '{$dateTimeNow->format('Y-m-d')}'
            ");
            $query->execute();
            $id = $query->fetch(\PDO::FETCH_ASSOC);

            if ($id === false || empty($id)) {
                $query = $this->databaseManager->prepare("
                    INSERT INTO a_Personnel_CardRecord (
                        fCode,
                        fDateTime,
                        fDateTimeScheduledIn,
                        fDateTimeScheduledOut,
                        fDateTimeUserIn,
                        fDateTimeUserOut,
                        fStatus
                    ) VALUES (
                        '{$presence['fCode']}',
                        '{$dateTimeNow->format('Y-m-d')}',"
                        . (! empty($presence['fDateTimeScheduledIn'])
                            ? "'{$presence['fDateTimeScheduledIn']}'"
                            : "NULL") . ","
                        . (! empty($presence['fDateTimeScheduledOut'])
                            ? "'{$presence['fDateTimeScheduledOut']}'"
                            : "NULL") . ","
                        . (! empty($cardRecord['fDateTimeUserIn'])
                            ? "'{$cardRecord['fDateTimeUserIn']}'"
                            : "NULL") . ","
                        . (! empty($cardRecord['fDateTimeUserOut'])
                            ? "'{$cardRecord['fDateTimeUserOut']}'"
                            : "NULL") . ","
                        . "'{$status}'
                    )
                ");
            } else {
                $query = $this->databaseManager->prepare("
                    UPDATE
                        a_Personnel_CardRecord
                    SET
                        fDateTimeScheduledIn = "
                        . (! empty($presence['fDateTimeScheduledIn'])
                            ? "'{$presence['fDateTimeScheduledIn']}'"
                            : "NULL") . ","
                        . "fDateTimeScheduledOut = "
                        . (! empty($presence['fDateTimeScheduledOut'])
                            ? "'{$presence['fDateTimeScheduledOut']}'"
                            : "NULL") . ","
                        . "fDateTimeUserIn = "
                        . (! empty($cardRecord['fDateTimeUserIn'])
                            ? "'{$cardRecord['fDateTimeUserIn']}'"
                            : "NULL") . ","
                        . "fDateTimeUserOut = "
                        . (! empty($cardRecord['fDateTimeUserOut'])
                            ? "'{$cardRecord['fDateTimeUserOut']}'"
                            : "NULL") . ","
                        . "fStatus = '{$status}'
                    WHERE
                        fId = {$id['fId']}
                ");
            }
            $query->execute();

            $this->incrementProgressBar($i);
        }

        $this->displayMessage(PHP_EOL . "Daily Presence Generation completed!");
    }
}
