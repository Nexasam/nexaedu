import InputError from '@/Components/InputError';
import Dropdown from '@/Components/Dropdown';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import SearchableSelect from '@/Components/SearchableSelect';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { AlertTriangle, ChevronDown, ChevronsUpDown, Download, Eye, FileSpreadsheet, Lock, Printer, Save, Search, Send, ShieldCheck, Upload, X } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

const ALL_SUBJECTS = 'all';

function subjectLabel(item) {
    return [
        item.class_arm?.name,
        item.school_subject?.custom_name || item.school_subject?.subject?.name,
        item.term?.name,
        item.academic_session?.name,
    ].filter(Boolean).join(' • ');
}

function studentName(student) {
    return [student.first_name, student.last_name].filter(Boolean).join(' ');
}

function classArmLabel(classArm) {
    return [
        classArm?.school_class?.class_group?.name,
        classArm?.school_class?.name,
        classArm?.name,
    ].filter(Boolean).join(' • ');
}

function classLabel(schoolClass) {
    return [schoolClass?.class_group?.name, schoolClass?.name].filter(Boolean).join(' • ');
}

function teacherName(teacher) {
    const staff = teacher?.staff_profile;

    return [staff?.first_name, staff?.middle_name, staff?.last_name].filter(Boolean).join(' ') || teacher?.staff_no || 'Teacher';
}

function subjectName(item) {
    return item.school_subject?.custom_name || item.school_subject?.subject?.name || 'Subject';
}

function studentIsEligibleForSubject(student, subject) {
    return student.enrollments?.some((enrollment) => (
        Number(enrollment.class_arm_id) === Number(subject.class_arm_id)
        && Number(enrollment.academic_session_id) === Number(subject.academic_session_id)
        && Number(enrollment.term_id) === Number(subject.term_id)
        && enrollment.status === 'active'
    ));
}

function positionLabel(position) {
    const suffix = position % 10 === 1 && position % 100 !== 11
        ? 'st'
        : position % 10 === 2 && position % 100 !== 12
            ? 'nd'
            : position % 10 === 3 && position % 100 !== 13
                ? 'rd'
                : 'th';

    return `${position}${suffix}`;
}

export default function Index({ classTermSubjects, assessmentGroups, students, teachers, scores, canOverrideCompleted, canOverrideSessionLock }) {
    const { auth } = usePage().props;
    const canWrite = auth.permissions?.score_entry === 'readwrite';
    const [scopeMode, setScopeMode] = useState('arm');
    const classArmOptions = useMemo(() => {
        const arms = new Map();

        classTermSubjects.forEach((item) => {
            if (item.class_arm) {
                arms.set(item.class_arm.id, { id: item.class_arm.id, name: classArmLabel(item.class_arm) });
            }
        });

        return Array.from(arms.values());
    }, [classTermSubjects]);
    const classOptions = useMemo(() => {
        const classes = new Map();

        classTermSubjects.forEach((item) => {
            const schoolClass = item.class_arm?.school_class;

            if (schoolClass) {
                classes.set(schoolClass.id, { id: schoolClass.id, name: classLabel(schoolClass) });
            }
        });

        return Array.from(classes.values());
    }, [classTermSubjects]);
    const teacherOptions = useMemo(() => teachers.map((teacher) => ({ id: teacher.id, name: teacherName(teacher) })), [teachers]);
    const [selectedClassArmId, setSelectedClassArmId] = useState('');
    const [selectedClassId, setSelectedClassId] = useState('');
    const [selectedTeacherId, setSelectedTeacherId] = useState('');
    const subjectsForArm = useMemo(() => classTermSubjects.filter((item) => Number(item.class_arm_id) === Number(selectedClassArmId)), [classTermSubjects, selectedClassArmId]);
    const scopeSubjects = useMemo(() => {
        if (scopeMode === 'class') {
            return classTermSubjects.filter((item) => Number(item.class_arm?.school_class?.id) === Number(selectedClassId));
        }

        if (scopeMode === 'teacher') {
            return classTermSubjects.filter((item) => item.teacher_assignments?.some((assignment) => Number(assignment.teacher_profile_id) === Number(selectedTeacherId)));
        }

        return subjectsForArm;
    }, [classTermSubjects, scopeMode, selectedClassId, selectedTeacherId, subjectsForArm]);
    const subjectOptions = subjectsForArm.length > 0
        ? [{ id: ALL_SUBJECTS, name: 'All subjects' }, ...subjectsForArm.map((item) => ({ id: item.id, name: subjectName(item) }))]
        : [];
    const [selectedClassTermSubjectId, setSelectedClassTermSubjectId] = useState('');
    const isAllSubjects = scopeMode !== 'arm' || selectedClassTermSubjectId === ALL_SUBJECTS;

    useEffect(() => {
        setSelectedClassTermSubjectId('');
    }, [selectedClassArmId]);

    useEffect(() => {
        setSelectedClassArmId('');
        setSelectedClassId('');
        setSelectedTeacherId('');
        setSelectedClassTermSubjectId('');
    }, [scopeMode]);

    const groupsForSubject = useMemo(() => assessmentGroups.filter((group) => Number(group.class_term_subject_id) === Number(selectedClassTermSubjectId)), [assessmentGroups, selectedClassTermSubjectId]);
    const [selectedAssessmentGroupId, setSelectedAssessmentGroupId] = useState(groupsForSubject[0]?.id ?? '');

    useEffect(() => {
        setSelectedAssessmentGroupId('');
    }, [selectedClassTermSubjectId]);

    const selectedSubject = classTermSubjects.find((item) => Number(item.id) === Number(selectedClassTermSubjectId));
    const selectedGroup = assessmentGroups.find((group) => Number(group.id) === Number(selectedAssessmentGroupId));
    const allSubjectSheets = useMemo(() => scopeSubjects.map((subject) => ({
        subject,
        group: assessmentGroups.find((group) => Number(group.class_term_subject_id) === Number(subject.id)),
    })).filter((sheet) => sheet.group), [assessmentGroups, scopeSubjects]);
    const groupOptions = groupsForSubject.map((group) => ({ id: group.id, name: group.name }));
    const isCompleted = Boolean(selectedGroup?.completed_at);
    const sessionLocked = Boolean(selectedSubject?.academic_session?.is_score_locked);
    const canEditScores = canWrite && (!isCompleted || canOverrideCompleted) && (!sessionLocked || canOverrideSessionLock);
    const eligibleStudents = useMemo(() => {
        if (!selectedSubject && !isAllSubjects) {
            return [];
        }

        return students.filter((student) => isAllSubjects
            ? allSubjectSheets.some(({ subject }) => studentIsEligibleForSubject(student, subject))
            : studentIsEligibleForSubject(student, selectedSubject));
    }, [allSubjectSheets, isAllSubjects, selectedSubject, students]);
    const scoreMap = useMemo(() => {
        const map = {};

        scores.forEach((score) => {
            map[`${score.student_profile_id}:${score.assessment_item_id}`] = score.score;
        });

        return map;
    }, [scores]);
    const [scoreValues, setScoreValues] = useState({});
    const studentTotals = useMemo(() => {
        if (!selectedGroup) {
            return {};
        }

        const totals = {};

        eligibleStudents.forEach((student) => {
            totals[student.id] = selectedGroup.items.reduce((sum, item) => sum + Number(scoreValues[`${student.id}:${item.id}`] || 0), 0);
        });

        return totals;
    }, [eligibleStudents, scoreValues, selectedGroup]);
    const studentPositions = useMemo(() => {
        const sortedTotals = [...new Set(Object.values(studentTotals).sort((a, b) => b - a))];
        const positions = {};

        Object.entries(studentTotals).forEach(([studentId, total]) => {
            positions[studentId] = sortedTotals.indexOf(total) + 1;
        });

        return positions;
    }, [studentTotals]);
    const importForm = useForm({
        assessment_group_id: selectedAssessmentGroupId,
        score_file: null,
    });
    const bulkImportForm = useForm({ score_file: null });
    const [studentSearch, setStudentSearch] = useState('');
    const [collapsedStudentIds, setCollapsedStudentIds] = useState([]);

    useEffect(() => {
        setStudentSearch('');
        setCollapsedStudentIds([]);
    }, [scopeMode, selectedClassArmId, selectedClassId, selectedTeacherId]);

    const filteredAllSubjectStudents = useMemo(() => {
        const search = studentSearch.trim().toLowerCase();

        if (!search) {
            return eligibleStudents;
        }

        return eligibleStudents.filter((student) => [studentName(student), student.admission_no]
            .filter(Boolean)
            .some((value) => String(value).toLowerCase().includes(search)));
    }, [eligibleStudents, studentSearch]);
    const allSubjectArmGroups = useMemo(() => {
        const groups = new Map();

        allSubjectSheets.forEach((sheet) => {
            const arm = sheet.subject.class_arm;

            if (!groups.has(arm.id)) {
                groups.set(arm.id, { arm, sheets: [] });
            }

            groups.get(arm.id).sheets.push(sheet);
        });

        return Array.from(groups.values()).map((group) => ({
            ...group,
            students: filteredAllSubjectStudents.filter((student) => group.sheets.some(({ subject }) => studentIsEligibleForSubject(student, subject))),
        })).filter((group) => group.students.length > 0);
    }, [allSubjectSheets, filteredAllSubjectStudents]);
    const bulkTemplateUrl = useMemo(() => {
        if (allSubjectSheets.length === 0 || filteredAllSubjectStudents.length === 0) {
            return '#';
        }

        const parameters = new URLSearchParams();
        allSubjectSheets.forEach(({ group }) => parameters.append('assessment_group_ids[]', group.id));
        filteredAllSubjectStudents.forEach((student) => parameters.append('student_profile_ids[]', student.id));

        return `${route('school.scores.bulk-template')}?${parameters.toString()}`;
    }, [allSubjectSheets, filteredAllSubjectStudents]);
    const [confirmation, setConfirmation] = useState(null);
    const [reportStudent, setReportStudent] = useState(null);
    const [tableSort, setTableSort] = useState({ key: 'position', direction: 'asc' });
    const sortedStudents = useMemo(() => {
        const direction = tableSort.direction === 'asc' ? 1 : -1;

        return [...eligibleStudents].sort((first, second) => {
            let firstValue;
            let secondValue;

            if (tableSort.key === 'student') {
                firstValue = studentName(first).toLowerCase();
                secondValue = studentName(second).toLowerCase();
            } else if (tableSort.key === 'admission_no') {
                firstValue = String(first.admission_no ?? '').toLowerCase();
                secondValue = String(second.admission_no ?? '').toLowerCase();
            } else if (tableSort.key === 'total') {
                firstValue = studentTotals[first.id] ?? 0;
                secondValue = studentTotals[second.id] ?? 0;
            } else {
                firstValue = studentPositions[first.id] ?? 999999;
                secondValue = studentPositions[second.id] ?? 999999;
            }

            if (typeof firstValue === 'number' && typeof secondValue === 'number') {
                return (firstValue - secondValue) * direction;
            }

            return String(firstValue).localeCompare(String(secondValue), undefined, { numeric: true, sensitivity: 'base' }) * direction;
        });
    }, [eligibleStudents, studentPositions, studentTotals, tableSort]);
    const buildReportRows = (student) => {
        if (!student || !selectedSubject) {
            return [];
        }

        const relatedSubjects = classTermSubjects.filter((item) => (
            Number(item.class_arm_id) === Number(selectedSubject.class_arm_id)
            && Number(item.academic_session_id) === Number(selectedSubject.academic_session_id)
            && Number(item.term_id) === Number(selectedSubject.term_id)
        ));

        return relatedSubjects.map((subject) => {
            const group = assessmentGroups.find((item) => Number(item.class_term_subject_id) === Number(subject.id));
            const total = group?.items?.reduce((sum, item) => sum + Number(scoreMap[`${student.id}:${item.id}`] || 0), 0) ?? 0;
            const hasAllScores = group?.items?.length > 0 && group.items.every((item) => {
                const score = scoreMap[`${student.id}:${item.id}`];

                return score !== undefined && score !== null && score !== '';
            });

            return {
                id: subject.id,
                subject: subjectName(subject),
                total,
                max: group?.total_score ?? 0,
                completed: Boolean(group?.completed_at) || hasAllScores,
            };
        });
    };

    const reportRows = useMemo(() => {
        return buildReportRows(reportStudent);
    }, [assessmentGroups, classTermSubjects, reportStudent, scoreMap, selectedSubject]);
    const reportReady = reportRows.length > 0 && reportRows.every((row) => row.completed);

    useEffect(() => {
        if (!selectedGroup && !isAllSubjects) {
            setScoreValues({});

            return;
        }

        const nextValues = {};
        eligibleStudents.forEach((student) => {
            const groups = isAllSubjects ? allSubjectSheets.map((sheet) => sheet.group) : [selectedGroup];

            groups.forEach((group) => {
                group.items.forEach((item) => {
                    nextValues[`${student.id}:${item.id}`] = scoreMap[`${student.id}:${item.id}`] ?? '';
                });
            });
        });
        setScoreValues(nextValues);
        importForm.setData('assessment_group_id', selectedGroup?.id ?? '');
    }, [allSubjectSheets, eligibleStudents, isAllSubjects, selectedGroup, scoreMap]);

    const updateScore = (studentId, itemId, value) => {
        setScoreValues((current) => ({
            ...current,
            [`${studentId}:${itemId}`]: value,
        }));
    };

    const toggleStudent = (studentId) => {
        setCollapsedStudentIds((current) => current.includes(studentId)
            ? current.filter((id) => id !== studentId)
            : [...current, studentId]);
    };

    const saveScores = () => {
        if (isAllSubjects) {
            const editableSheets = allSubjectSheets.filter(({ subject, group }) => (
                canWrite
                && eligibleStudents.some((student) => studentIsEligibleForSubject(student, subject))
                && (!group.completed_at || canOverrideCompleted)
                && (!subject.academic_session?.is_score_locked || canOverrideSessionLock)
            ));

            if (editableSheets.length === 0) {
                return;
            }

            router.post(route('school.scores.bulk-store'), {
                score_sheets: editableSheets.map(({ subject, group }) => ({
                    assessment_group_id: group.id,
                    scores: eligibleStudents.filter((student) => studentIsEligibleForSubject(student, subject)).map((student) => ({
                        student_profile_id: student.id,
                        items: group.items.map((item) => ({
                            assessment_item_id: item.id,
                            score: scoreValues[`${student.id}:${item.id}`] ?? '',
                        })),
                    })),
                })),
            }, {
                preserveScroll: true,
                preserveState: true,
            });

            return;
        }

        if (!selectedGroup) {
            return;
        }

        router.post(route('school.scores.store'), {
            assessment_group_id: selectedGroup.id,
            scores: eligibleStudents.map((student) => ({
                student_profile_id: student.id,
                items: selectedGroup.items.map((item) => ({
                    assessment_item_id: item.id,
                    score: scoreValues[`${student.id}:${item.id}`] ?? '',
                })),
            })),
        }, {
            preserveScroll: true,
            preserveState: true,
        });
    };

    const submitScores = (event) => {
        event.preventDefault();

        setConfirmation({
            title: isAllSubjects ? 'Save all subject scores?' : 'Save score entries?',
            description: isAllSubjects
                ? `This will save ${allSubjectSheets.length} subject score sheets for every listed student. Completed or session-locked sheets that you cannot override will remain unchanged.`
                : 'This will save the scores currently entered in the matrix for this class arm, subject, term, and assessment flow. You can still adjust them later unless the sheet is marked as fully completed.',
            actionLabel: isAllSubjects ? 'Save all scores' : 'Save scores',
            onConfirm: saveScores,
        });
    };

    const importScores = () => {
        importForm.post(route('school.scores.import'), {
            preserveScroll: true,
            preserveState: true,
            forceFormData: true,
            onSuccess: () => importForm.reset('score_file'),
        });
    };

    const submitImport = (event) => {
        event.preventDefault();

        setConfirmation({
            title: 'Import score sheet?',
            description: 'This will read the uploaded CSV and update the matching students and assessment columns for the selected assessment flow. Existing values in the uploaded columns may be replaced.',
            actionLabel: 'Import scores',
            onConfirm: importScores,
        });
    };

    const importBulkScores = () => {
        bulkImportForm
            .transform((data) => ({
                ...data,
                assessment_group_ids: allSubjectSheets.map(({ group }) => group.id),
            }))
            .post(route('school.scores.bulk-import'), {
                preserveScroll: true,
                preserveState: true,
                forceFormData: true,
                onSuccess: () => bulkImportForm.reset('score_file'),
            });
    };

    const submitBulkImport = (event) => {
        event.preventDefault();
        setConfirmation({
            title: 'Import filtered score sheets?',
            description: `This will validate and update ${allSubjectSheets.length} subject score sheets from the uploaded CSV. Existing scores in completed or locked sheets cannot be changed without the required override.`,
            actionLabel: 'Import scores',
            onConfirm: importBulkScores,
        });
    };

    const markCompleted = () => {
        if (!selectedGroup) {
            return;
        }

        router.post(route('school.scores.complete', selectedGroup.id), {}, {
            preserveScroll: true,
            preserveState: true,
        });
    };

    const completeScores = () => {
        setConfirmation({
            title: 'Mark score sheet as fully completed?',
            description: 'This locks the score sheet for normal staff and teachers. After this, only the school super admin can change these scores.',
            actionLabel: 'Mark completed',
            onConfirm: markCompleted,
            danger: true,
        });
    };

    const sendToParent = (student) => {
        const studentReportRows = buildReportRows(student);
        const reportIsReady = studentReportRows.length > 0 && studentReportRows.every((row) => row.completed);

        setConfirmation({
            title: 'Send report to parent?',
            description: reportIsReady
                ? `This will notify the parent/guardian that ${studentName(student)}'s report is ready for this term.`
                : 'This report card is not ready because one or more subjects are not fully completed.',
            actionLabel: 'Send to parent',
            danger: !reportIsReady,
            onConfirm: () => {
                if (!reportIsReady || !selectedGroup) {
                    return;
                }

                router.post(route('school.scores.send-parent', [selectedGroup.id, student.id]), {}, {
                    preserveScroll: true,
                    preserveState: true,
                });
            },
        });
    };

    const confirmAction = () => {
        confirmation?.onConfirm();
        setConfirmation(null);
    };

    const changeTableSort = (key) => {
        setTableSort((current) => ({
            key,
            direction: current.key === key && current.direction === 'asc' ? 'desc' : 'asc',
        }));
    };

    const SortableHeader = ({ sortKey, children }) => (
        <button type="button" onClick={() => changeTableSort(sortKey)} className="inline-flex items-center gap-2 transition hover:text-slate-900">
            {children}
            <ChevronsUpDown className="h-3.5 w-3.5" aria-hidden="true" strokeWidth={2.25} />
        </button>
    );

    return (
        <AuthenticatedLayout title="Score entry" subtitle="Choose a class arm, select a subject, then enter scores in the assessment matrix.">
            <Head title="Score entry" />

            <Modal show={Boolean(confirmation)} closeable={false} maxWidth="md">
                <div className="p-5">
                    <div className="flex items-start justify-between gap-4">
                        <div className="flex gap-3">
                            <span className={[
                                'grid h-10 w-10 shrink-0 place-items-center rounded-lg',
                                confirmation?.danger ? 'bg-amber-50 text-amber-700' : 'bg-[var(--brand-primary-soft)] text-[var(--brand-primary)]',
                            ].join(' ')}>
                                <AlertTriangle className="h-5 w-5" aria-hidden="true" strokeWidth={2.25} />
                            </span>
                            <div>
                                <h2 className="text-base font-bold text-slate-950">{confirmation?.title}</h2>
                                <p className="mt-1 text-sm leading-6 text-slate-600">{confirmation?.description}</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            onClick={() => setConfirmation(null)}
                            className="grid h-8 w-8 place-items-center rounded-md text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                            aria-label="Close confirmation"
                        >
                            <X className="h-4 w-4" aria-hidden="true" strokeWidth={2.25} />
                        </button>
                    </div>
                    <div className="mt-5 flex justify-end gap-2">
                        <SecondaryButton onClick={() => setConfirmation(null)} className="normal-case tracking-normal">
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton type="button" onClick={confirmAction}>
                            {confirmation?.actionLabel}
                        </PrimaryButton>
                    </div>
                </div>
            </Modal>

            <Modal show={Boolean(reportStudent)} closeable={false} maxWidth="2xl">
                <div className="max-h-[88vh] overflow-y-auto">
                    <div className="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4">
                        <div>
                            <h2 className="text-base font-bold text-slate-950">Report card</h2>
                            <p className="mt-1 text-sm text-slate-500">{reportStudent ? `${studentName(reportStudent)} • ${reportStudent.admission_no}` : ''}</p>
                        </div>
                        <button
                            type="button"
                            onClick={() => setReportStudent(null)}
                            className="grid h-8 w-8 place-items-center rounded-md text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                            aria-label="Close report card"
                        >
                            <X className="h-4 w-4" aria-hidden="true" strokeWidth={2.25} />
                        </button>
                    </div>
                    <div className="p-5">
                        {!reportReady && (
                            <div className="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-3 py-3 text-sm font-semibold text-amber-800">
                                Report card not ready. One or more subjects are not fully completed yet.
                            </div>
                        )}
                        <div className="overflow-hidden rounded-lg border border-slate-200">
                            <table className="min-w-full divide-y divide-slate-200 text-sm">
                                <thead className="bg-slate-50">
                                    <tr>
                                        <th className="px-3 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Subject</th>
                                        <th className="px-3 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Score</th>
                                        <th className="px-3 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Status</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 bg-white">
                                    {reportRows.map((row) => (
                                        <tr key={row.id}>
                                            <td className="px-3 py-3 font-semibold text-slate-950">{row.subject}</td>
                                            <td className="px-3 py-3 font-bold text-slate-700">{row.total}/{row.max}</td>
                                            <td className="px-3 py-3">
                                                <span className={[
                                                    'rounded-full px-2.5 py-1 text-xs font-bold',
                                                    row.completed ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700',
                                                ].join(' ')}>
                                                    {row.completed ? 'Completed' : 'Not ready'}
                                                </span>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </Modal>

            <div className="grid gap-4 xl:grid-cols-[minmax(0,1fr)_340px]">
                <section className="rounded-xl border border-[var(--brand-primary-border)] bg-white p-4 shadow-sm">
                    <div className="mb-4">
                        <span className="text-xs font-bold uppercase tracking-wide text-slate-500">View scores by</span>
                        <div className="mt-1 inline-flex rounded-md border border-slate-200 bg-slate-50 p-1">
                            {[
                                ['arm', 'Class arm'],
                                ['class', 'Class'],
                                ['teacher', 'Teacher'],
                            ].map(([value, label]) => (
                                <button
                                    key={value}
                                    type="button"
                                    onClick={() => setScopeMode(value)}
                                    className={[
                                        'rounded px-3 py-1.5 text-sm font-bold transition',
                                        scopeMode === value ? 'bg-[var(--brand-primary)] text-white shadow-sm' : 'text-slate-600 hover:bg-white hover:text-slate-900',
                                    ].join(' ')}
                                >
                                    {label}
                                </button>
                            ))}
                        </div>
                    </div>
                    <div className="grid gap-3 lg:grid-cols-3">
                        <label className="block">
                            <span className="text-xs font-bold uppercase tracking-wide text-slate-500">
                                {scopeMode === 'class' ? 'Class' : scopeMode === 'teacher' ? 'Teacher' : 'Class arm'}
                            </span>
                            {scopeMode === 'class' ? (
                                <SearchableSelect id="class_id" value={selectedClassId} options={classOptions} searchPlaceholder="Search classes" placeholder="No matching classes." onChange={setSelectedClassId} />
                            ) : scopeMode === 'teacher' ? (
                                <SearchableSelect id="teacher_id" value={selectedTeacherId} options={teacherOptions} searchPlaceholder="Search teachers" placeholder="No assigned teachers." onChange={setSelectedTeacherId} />
                            ) : (
                                <SearchableSelect id="class_arm_id" value={selectedClassArmId} options={classArmOptions} searchPlaceholder="Search class arms" placeholder="No matching class arms." onChange={setSelectedClassArmId} />
                            )}
                        </label>
                        <label className="block">
                            <span className="text-xs font-bold uppercase tracking-wide text-slate-500">Subject</span>
                            {scopeMode === 'arm' ? (
                                <SearchableSelect id="class_term_subject_id" value={selectedClassTermSubjectId} options={subjectOptions} searchPlaceholder="Search class arm subjects" placeholder="No matching subjects." onChange={setSelectedClassTermSubjectId} />
                            ) : (
                                <div className="mt-1 flex min-h-10 items-center rounded-md border border-[var(--brand-primary-border)] bg-[var(--brand-primary-soft)] px-3 text-sm font-semibold text-[var(--brand-primary)]">
                                    All assigned subjects
                                </div>
                            )}
                        </label>
                        <label className="block">
                            <span className="text-xs font-bold uppercase tracking-wide text-slate-500">Assessment flow</span>
                            {isAllSubjects ? (
                                <div className="mt-1 flex min-h-10 items-center rounded-md border border-[var(--brand-primary-border)] bg-[var(--brand-primary-soft)] px-3 text-sm font-semibold text-[var(--brand-primary)]">
                                    Each subject's assigned flow
                                </div>
                            ) : (
                                <SearchableSelect
                                    id="assessment_group_id"
                                    value={selectedAssessmentGroupId}
                                    options={groupOptions}
                                    searchPlaceholder="Search assessment flows"
                                    placeholder="No matching flows."
                                    onChange={(id) => {
                                        setSelectedAssessmentGroupId(id);
                                        importForm.setData('assessment_group_id', id);
                                    }}
                                />
                            )}
                        </label>
                    </div>

                    {isCompleted && (
                        <div className="mt-4 flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-3 text-sm text-amber-800">
                            <Lock className="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" strokeWidth={2.25} />
                            <div>
                                <div className="font-bold">Marked as fully completed</div>
                                <div className="mt-0.5">Only the school super admin can change this score sheet now.</div>
                            </div>
                        </div>
                    )}

                    {sessionLocked && (
                        <div className="mt-4 flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 px-3 py-3 text-sm text-red-800">
                            <Lock className="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" strokeWidth={2.25} />
                            <div><div className="font-bold">Score entry is locked for {selectedSubject?.academic_session?.name}</div><div className="mt-0.5">{canOverrideSessionLock ? 'You can still edit because you are the school super admin.' : 'Teachers and staff cannot save, import, or complete scores until the session is unlocked.'}</div></div>
                        </div>
                    )}

                    {isAllSubjects ? (
                        <form onSubmit={submitScores} className="mt-4 space-y-4">
                            <div className="relative max-w-sm">
                                <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" aria-hidden="true" strokeWidth={2} />
                                <input
                                    type="search"
                                    value={studentSearch}
                                    onChange={(event) => setStudentSearch(event.target.value)}
                                    placeholder="Search student or admission no."
                                    aria-label="Search students"
                                    className="block h-9 w-full rounded-md border-slate-300 pl-9 pr-9 text-sm shadow-sm focus:border-[var(--brand-primary)] focus:ring-[var(--brand-primary)]"
                                />
                                {studentSearch && (
                                    <button
                                        type="button"
                                        onClick={() => setStudentSearch('')}
                                        className="absolute right-1 top-1/2 grid h-7 w-7 -translate-y-1/2 place-items-center rounded-md text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                                        aria-label="Clear student search"
                                    >
                                        <X className="h-3.5 w-3.5" aria-hidden="true" />
                                    </button>
                                )}
                            </div>
                            {allSubjectSheets.length === 0 ? (
                                <div className="rounded-lg border border-dashed border-slate-300 bg-slate-50 p-6 text-center text-sm font-medium text-slate-500">
                                    No assessment flows are available for the selected {scopeMode === 'teacher' ? 'teacher' : scopeMode === 'class' ? 'class' : 'class arm'}.
                                </div>
                            ) : eligibleStudents.length === 0 ? (
                                <div className="rounded-lg bg-slate-50 p-4 text-sm font-medium text-slate-500">
                                    No active students are enrolled for this selection.
                                </div>
                            ) : filteredAllSubjectStudents.length === 0 ? (
                                <div className="rounded-lg border border-dashed border-slate-300 bg-slate-50 p-5 text-center text-sm font-medium text-slate-500">
                                    No student matches your search.
                                </div>
                            ) : (
                                allSubjectArmGroups.map(({ arm, sheets, students: armStudents }) => (
                                    <section key={arm.id} className="space-y-3">
                                        {scopeMode !== 'arm' && (
                                            <header className="flex items-center justify-between gap-3 border-b border-[var(--brand-primary-border)] pb-2">
                                                <div>
                                                    <h2 className="text-sm font-bold text-slate-950">{classArmLabel(arm)}</h2>
                                                    <p className="mt-0.5 text-xs text-slate-500">{armStudents.length} students • {sheets.length} assigned subjects</p>
                                                </div>
                                            </header>
                                        )}
                                        {armStudents.map((student) => {
                                            const disclosureId = `${arm.id}:${student.id}`;
                                            const studentSheets = sheets.filter(({ subject }) => studentIsEligibleForSubject(student, subject));

                                            return (
                                    <section key={disclosureId} className="overflow-hidden rounded-lg border border-slate-200 bg-white">
                                        <button
                                            type="button"
                                            onClick={() => toggleStudent(disclosureId)}
                                            className="flex w-full flex-wrap items-center justify-between gap-3 bg-slate-50 px-4 py-3 text-left transition hover:bg-slate-100"
                                            aria-expanded={!collapsedStudentIds.includes(disclosureId)}
                                            aria-controls={`student-subjects-${arm.id}-${student.id}`}
                                        >
                                            <div className="flex min-w-0 items-center gap-3">
                                                <ChevronDown
                                                    className={[
                                                        'h-4 w-4 shrink-0 text-slate-400 transition-transform',
                                                        collapsedStudentIds.includes(disclosureId) ? '-rotate-90' : '',
                                                    ].join(' ')}
                                                    aria-hidden="true"
                                                    strokeWidth={2.25}
                                                />
                                                <div className="min-w-0">
                                                    <h3 className="truncate text-sm font-bold text-slate-950">{studentName(student)}</h3>
                                                    <p className="mt-0.5 text-xs font-medium text-slate-500">{student.admission_no}</p>
                                                </div>
                                            </div>
                                            <span className="rounded-full bg-[var(--brand-primary-soft)] px-2.5 py-1 text-xs font-bold text-[var(--brand-primary)]">
                                                {studentSheets.length} subjects
                                            </span>
                                        </button>
                                        {!collapsedStudentIds.includes(disclosureId) && (
                                        <div id={`student-subjects-${arm.id}-${student.id}`} className="grid gap-3 border-t border-slate-200 p-3 md:grid-cols-2">
                                            {studentSheets.map(({ subject, group }) => {
                                                const groupLocked = Boolean(group.completed_at) || Boolean(subject.academic_session?.is_score_locked);
                                                const canEditGroup = canWrite
                                                    && (!group.completed_at || canOverrideCompleted)
                                                    && (!subject.academic_session?.is_score_locked || canOverrideSessionLock);
                                                const total = group.items.reduce((sum, item) => sum + Number(scoreValues[`${student.id}:${item.id}`] || 0), 0);

                                                return (
                                                    <div key={group.id} className="rounded-md border border-slate-200 bg-white p-3 shadow-sm">
                                                        <div className="flex items-start justify-between gap-2">
                                                            <div className="min-w-0">
                                                                <div className="truncate text-sm font-bold text-slate-900">{subjectName(subject)}</div>
                                                                <div className="mt-0.5 truncate text-xs text-slate-500" title={`${group.name} • ${subject.term?.name} • ${subject.academic_session?.name}`}>
                                                                    {group.name} • {subject.term?.name} • {subject.academic_session?.name}
                                                                </div>
                                                            </div>
                                                            <div className="shrink-0 text-right">
                                                                <div className="text-xs font-bold uppercase text-slate-400">Total</div>
                                                                <div className="text-sm font-bold text-[var(--brand-primary)]">{total}/{group.total_score}</div>
                                                            </div>
                                                        </div>
                                                        <div className="mt-2 flex min-h-5 flex-wrap items-center gap-1.5">
                                                                {groupLocked && (
                                                                    <span className="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 font-bold text-amber-700">
                                                                        <Lock className="h-3 w-3" aria-hidden="true" /> Locked
                                                                    </span>
                                                                )}
                                                        </div>
                                                        <div className="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-3">
                                                            {group.items.map((item) => (
                                                                <label key={item.id} className="block min-w-0">
                                                                    <span className="block truncate text-[11px] font-bold text-slate-500" title={`${item.name} / ${item.max_score}`}>{item.name} / {item.max_score}</span>
                                                                    <input
                                                                        type="number"
                                                                        min="0"
                                                                        max={item.raw_max_score ?? item.max_score}
                                                                        step="0.01"
                                                                        disabled={!canEditGroup}
                                                                        value={scoreValues[`${student.id}:${item.id}`] ?? ''}
                                                                        onChange={(event) => updateScore(student.id, item.id, event.target.value)}
                                                                        className="mt-1 block h-9 w-full rounded-md border-slate-300 px-2 py-1 text-sm shadow-sm focus:border-[var(--brand-primary)] focus:ring-[var(--brand-primary)] disabled:bg-slate-100"
                                                                    />
                                                                </label>
                                                            ))}
                                                        </div>
                                                    </div>
                                                );
                                            })}
                                        </div>
                                        )}
                                    </section>
                                            );
                                        })}
                                    </section>
                                ))
                            )}
                            {canWrite && allSubjectSheets.length > 0 && eligibleStudents.length > 0 && (
                                <div className="flex justify-end">
                                    <PrimaryButton>
                                        <Save className="mr-2 h-4 w-4" aria-hidden="true" strokeWidth={2.25} />
                                        Save all subject scores
                                    </PrimaryButton>
                                </div>
                            )}
                        </form>
                    ) : !selectedGroup ? (
                        <div className="mt-4 rounded-lg border border-dashed border-slate-300 bg-slate-50 p-6 text-center text-sm font-medium text-slate-500">
                            No assessment flow has been created for this class arm subject yet.
                        </div>
                    ) : (
                        <form onSubmit={submitScores} className="mt-4">
                            <div className="overflow-x-auto rounded-lg border border-slate-200 pb-24">
                                <table className="min-w-full divide-y divide-slate-200 text-sm">
                                    <thead className="bg-slate-50">
                                        <tr>
                                            <th className="whitespace-nowrap px-3 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                                                <SortableHeader sortKey="student">Student</SortableHeader>
                                            </th>
                                            <th className="whitespace-nowrap px-3 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                                                <SortableHeader sortKey="admission_no">Admission no</SortableHeader>
                                            </th>
                                            {selectedGroup.items.map((item) => (
                                                <th key={item.id} className="whitespace-nowrap px-3 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                                                    {item.name}
                                                    <span className="ml-1 font-semibold normal-case text-slate-400">/{item.max_score}</span>
                                                </th>
                                            ))}
                                            <th className="whitespace-nowrap px-3 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                                                <SortableHeader sortKey="total">Total</SortableHeader>
                                            </th>
                                            <th className="whitespace-nowrap px-3 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                                                <SortableHeader sortKey="position">Position so far</SortableHeader>
                                            </th>
                                            <th className="min-w-44 whitespace-nowrap px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100 bg-white">
                                        {sortedStudents.map((student) => {
                                            const total = studentTotals[student.id] ?? 0;

                                            return (
                                                <tr key={student.id}>
                                                    <td className="whitespace-nowrap px-3 py-3 font-semibold text-slate-950">{studentName(student)}</td>
                                                    <td className="whitespace-nowrap px-3 py-3 text-slate-500">{student.admission_no}</td>
                                                    {selectedGroup.items.map((item) => (
                                                        <td key={item.id} className="min-w-28 px-3 py-2">
                                                            <input
                                                                type="number"
                                                                min="0"
                                                                max={item.max_score}
                                                                step="0.01"
                                                                disabled={!canEditScores}
                                                                value={scoreValues[`${student.id}:${item.id}`] ?? ''}
                                                                onChange={(event) => updateScore(student.id, item.id, event.target.value)}
                                                                className="w-24 rounded-md border-slate-300 text-sm shadow-sm focus:border-[var(--brand-primary)] focus:ring-[var(--brand-primary)] disabled:bg-slate-100"
                                                            />
                                                        </td>
                                                    ))}
                                                    <td className="whitespace-nowrap px-3 py-3 font-bold text-slate-700">{total}</td>
                                                    <td className="whitespace-nowrap px-3 py-3 font-bold text-[var(--brand-primary)]">{positionLabel(studentPositions[student.id] ?? 1)}</td>
                                                    <td className="min-w-44 whitespace-nowrap px-4 py-3">
                                                        <Dropdown>
                                                            <Dropdown.Trigger>
                                                                <button
                                                                    type="button"
                                                                    className="inline-flex w-32 items-center justify-between gap-2 rounded-md border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 transition hover:bg-slate-50"
                                                                >
                                                                    Actions
                                                                    <ChevronDown className="h-3.5 w-3.5" aria-hidden="true" strokeWidth={2.25} />
                                                                </button>
                                                            </Dropdown.Trigger>
                                                            <Dropdown.Content width="56">
                                                                <button
                                                                    type="button"
                                                                    onClick={() => {
                                                                        setReportStudent(student);
                                                                    }}
                                                                    className="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-slate-700 transition hover:bg-slate-100"
                                                                >
                                                                    <Eye className="h-4 w-4" aria-hidden="true" strokeWidth={2.25} />
                                                                    View report card
                                                                </button>
                                                                <button
                                                                    type="button"
                                                                    onClick={() => {
                                                                        setReportStudent(student);
                                                                        sendToParent(student);
                                                                    }}
                                                                    className="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-slate-700 transition hover:bg-slate-100"
                                                                >
                                                                    <Send className="h-4 w-4" aria-hidden="true" strokeWidth={2.25} />
                                                                    Send to parent
                                                                </button>
                                                            </Dropdown.Content>
                                                        </Dropdown>
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                            {eligibleStudents.length === 0 && (
                                <div className="mt-3 rounded-lg bg-slate-50 p-4 text-sm font-medium text-slate-500">
                                    No active students are enrolled in this class arm for the selected session and term.
                                </div>
                            )}
                            {canEditScores && (
                                <div className="mt-4 flex flex-col gap-2 sm:flex-row sm:justify-end">
                                    <SecondaryButton type="button" onClick={completeScores} disabled={eligibleStudents.length === 0} className="justify-center normal-case tracking-normal">
                                        <Lock className="mr-2 h-4 w-4" aria-hidden="true" strokeWidth={2.25} />
                                        Mark as fully completed
                                    </SecondaryButton>
                                    <PrimaryButton disabled={eligibleStudents.length === 0}>
                                        <Save className="mr-2 h-4 w-4" aria-hidden="true" strokeWidth={2.25} />
                                        Save scores
                                    </PrimaryButton>
                                </div>
                            )}
                        </form>
                    )}
                </section>

                <aside className="space-y-4">
                    <section className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div className="flex items-center gap-3">
                            <span className="grid h-10 w-10 place-items-center rounded-lg bg-[var(--brand-primary-soft)] text-[var(--brand-primary)]">
                                <ShieldCheck className="h-5 w-5" aria-hidden="true" strokeWidth={2.25} />
                            </span>
                            <div>
                                <h2 className="text-base font-bold text-slate-950">School readiness</h2>
                                <p className="text-sm text-slate-500">Review all student report cards by class arm.</p>
                            </div>
                        </div>
                        <Link
                            href={route('school.reports.readiness')}
                            className="mt-4 inline-flex w-full items-center justify-center rounded-lg border border-[var(--brand-primary-border)] bg-[var(--brand-primary-soft)] px-4 py-2 text-sm font-bold text-[var(--brand-primary)] transition hover:brightness-95"
                        >
                            View readiness report
                        </Link>
                    </section>

                    <section className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div className="flex items-center gap-3">
                            <span className="grid h-10 w-10 place-items-center rounded-lg bg-[var(--brand-primary-soft)] text-[var(--brand-primary)]">
                                <FileSpreadsheet className="h-5 w-5" aria-hidden="true" strokeWidth={2.25} />
                            </span>
                            <div>
                                <h2 className="text-base font-bold text-slate-950">Template import</h2>
                                <p className="text-sm text-slate-500">
                                    {isAllSubjects ? 'Download the current filtered rows, fill them offline, then upload the same CSV.' : 'Download, fill in Excel, then upload the same CSV.'}
                                </p>
                            </div>
                        </div>

                        {selectedGroup && (
                            <div className="mt-4 grid gap-3">
                                <a
                                    href={route('school.scores.template', selectedGroup.id)}
                                    className="inline-flex items-center justify-center rounded-lg border border-[var(--brand-primary-border)] bg-[var(--brand-primary-soft)] px-4 py-2 text-sm font-bold text-[var(--brand-primary)] transition hover:brightness-95"
                                >
                                    <Download className="mr-2 h-4 w-4" aria-hidden="true" strokeWidth={2.25} />
                                    Download template
                                </a>
                                <button
                                    type="button"
                                    onClick={() => window.print()}
                                    className="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 transition hover:bg-slate-50"
                                >
                                    <Printer className="mr-2 h-4 w-4" aria-hidden="true" strokeWidth={2.25} />
                                    Print / save PDF
                                </button>

                                {canEditScores && (
                                    <form onSubmit={submitImport} className="grid gap-3">
                                        <input
                                            type="file"
                                            accept=".csv,text/csv"
                                            onChange={(event) => importForm.setData('score_file', event.target.files[0])}
                                            className="block w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm file:mr-3 file:rounded-md file:border-0 file:bg-[var(--brand-primary-soft)] file:px-3 file:py-1.5 file:text-sm file:font-bold file:text-[var(--brand-primary)]"
                                        />
                                        <InputError message={importForm.errors.score_file || importForm.errors.assessment_group_id} />
                                        {importForm.progress && (
                                            <div className="h-2 overflow-hidden rounded-full bg-slate-100">
                                                <div className="h-full rounded-full bg-[var(--brand-primary)]" style={{ width: `${importForm.progress.percentage}%` }} />
                                            </div>
                                        )}
                                        <SecondaryButton disabled={!importForm.data.score_file || importForm.processing} className="justify-center normal-case tracking-normal">
                                            <Upload className="mr-2 h-4 w-4" aria-hidden="true" strokeWidth={2.25} />
                                            Upload scores
                                        </SecondaryButton>
                                    </form>
                                )}
                            </div>
                        )}
                        {isAllSubjects && allSubjectSheets.length > 0 && (
                            <div className="mt-4 grid gap-3">
                                {filteredAllSubjectStudents.length > 0 ? (
                                    <a
                                        href={bulkTemplateUrl}
                                        className="inline-flex items-center justify-center rounded-lg border border-[var(--brand-primary-border)] bg-[var(--brand-primary-soft)] px-4 py-2 text-sm font-bold text-[var(--brand-primary)] transition hover:brightness-95"
                                    >
                                        <Download className="mr-2 h-4 w-4" aria-hidden="true" strokeWidth={2.25} />
                                        Download filtered CSV
                                    </a>
                                ) : (
                                    <button type="button" disabled className="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-slate-100 px-4 py-2 text-sm font-bold text-slate-400">
                                        <Download className="mr-2 h-4 w-4" aria-hidden="true" strokeWidth={2.25} />
                                        No rows to download
                                    </button>
                                )}
                                <p className="text-xs leading-5 text-slate-500">
                                    Includes {filteredAllSubjectStudents.length} visible students across {allSubjectSheets.length} subject flows.
                                </p>
                                {canWrite && (
                                    <form onSubmit={submitBulkImport} className="grid gap-3">
                                        <input
                                            type="file"
                                            accept=".csv,text/csv"
                                            onChange={(event) => bulkImportForm.setData('score_file', event.target.files[0])}
                                            className="block w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm file:mr-3 file:rounded-md file:border-0 file:bg-[var(--brand-primary-soft)] file:px-3 file:py-1.5 file:text-sm file:font-bold file:text-[var(--brand-primary)]"
                                        />
                                        <InputError message={bulkImportForm.errors.score_file || bulkImportForm.errors.assessment_group_ids} />
                                        {bulkImportForm.progress && (
                                            <div className="h-2 overflow-hidden rounded-full bg-slate-100">
                                                <div className="h-full rounded-full bg-[var(--brand-primary)]" style={{ width: `${bulkImportForm.progress.percentage}%` }} />
                                            </div>
                                        )}
                                        <SecondaryButton disabled={!bulkImportForm.data.score_file || bulkImportForm.processing} className="justify-center normal-case tracking-normal">
                                            <Upload className="mr-2 h-4 w-4" aria-hidden="true" strokeWidth={2.25} />
                                            Upload filtered scores
                                        </SecondaryButton>
                                    </form>
                                )}
                            </div>
                        )}
                    </section>

                    <section className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <h2 className="text-base font-bold text-slate-950">Current flow</h2>
                        {isAllSubjects ? (
                            <div className="mt-3 space-y-2">
                                {allSubjectSheets.map(({ subject, group }) => (
                                    <div key={group.id} className="flex items-center justify-between gap-3 rounded-lg bg-slate-50 px-3 py-2 text-sm">
                                        <span className="font-medium text-slate-700">{subjectName(subject)}</span>
                                        <span className="text-right text-xs font-bold text-[var(--brand-primary)]">{group.name}</span>
                                    </div>
                                ))}
                            </div>
                        ) : selectedGroup ? (
                            <div className="mt-3 space-y-2">
                                <div className="text-sm font-semibold text-slate-700">{selectedGroup.name}</div>
                                {selectedGroup.items.map((item) => (
                                    <div key={item.id} className="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2 text-sm">
                                        <span className="font-medium text-slate-700">{item.name}</span>
                                        <span className="font-bold text-[var(--brand-primary)]">{item.max_score}</span>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <p className="mt-2 text-sm text-slate-500">Choose a subject with an assessment flow.</p>
                        )}
                    </section>
                </aside>
            </div>
        </AuthenticatedLayout>
    );
}
