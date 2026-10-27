<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'employee_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Helper untuk cek role
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function hasAnyRole(array|string $roles): bool
    {
        if (is_string($roles)) {
            $roles = explode(',', $roles);
        }
        return in_array($this->role, $roles);
    }

    /**
     * Check if user has an admin-level role.
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, ['super_admin', 'admin_sd', 'admin_paud', 'admin_smp', 'kepala_sekolah', 'waka']);
    }

    /**
     * Check if the user is currently assigned as an active homeroom teacher in the active academic year.
     */
    public function isActiveHomeroomTeacher(): bool
    {
        if ($this->role === 'wali_kelas') {
            return true;
        }

        if (!$this->employee_id) {
            return false;
        }

        return \Illuminate\Support\Facades\Cache::remember('user_is_active_homeroom_' . $this->employee_id, 60, function () {
            if (\App\Models\Classroom::where('homeroom_teacher_id', $this->employee_id)->exists()) {
                return true;
            }

            $activeAy = \App\Models\AcademicYear::where('is_active', true)->first();
            if (!$activeAy) {
                return false;
            }

            return \App\Models\HomeroomAssignment::where('employee_id', $this->employee_id)
                ->where('academic_year_id', $activeAy->id)
                ->where('is_active', true)
                ->exists();
        });
    }

    /**
     * Check if the user is an extracurricular coordinator in E-Rapor.
     */
    public function isExtraCoordinator(): bool
    {
        if (in_array($this->role, ['koordinator_ekstra', 'koordinator'])) {
            return true;
        }

        $userId = $this->id;
        return \Illuminate\Support\Facades\Cache::remember('user_is_extra_coord_' . $userId, 120, function () {
            $cleanName = trim(explode(',', $this->name)[0]);
            if (strlen($cleanName) < 3) {
                return false;
            }

            try {
                $raporDb = \App\Models\Setting::get('rapor_db_name', env('DB_RAPOR_DATABASE', 'sans-rapor'));
                $baseConfig = config('database.connections.sans_rapor') ?: config('database.connections.mysql');
                $baseConfig['database'] = $raporDb;
                config(['database.connections.sans_rapor' => $baseConfig]);

                return \Illuminate\Support\Facades\DB::connection('sans_rapor')
                    ->table('paud_extracurricular_activities')
                    ->where('is_active', true)
                    ->where('unit', 'paud')
                    ->where(function ($q) use ($cleanName) {
                        $q->where('coordinator_name', 'like', "%{$cleanName}%")
                          ->orWhere('level_coordinators', 'like', "%{$cleanName}%");

                        $emp = $this->employee;
                        if ($emp && !empty($emp->niy) && $emp->niy !== '-') {
                            $q->orWhere('coordinator_niy', $emp->niy)
                              ->orWhere('level_coordinators', 'like', "%{$emp->niy}%");
                        }
                    })
                    ->exists();
            } catch (\Throwable $e) {
                return false;
            }
        });
    }

    /**
     * Check if the user can access E-Rapor (Super Admin, Kepala Sekolah, Admin, Active Homeroom Teachers, or Extracurricular Coordinators).
     */
    public function canAccessRapor(): bool
    {
        if (in_array($this->role, ['super_admin', 'kepala_sekolah', 'admin_paud', 'admin_sd', 'admin_smp', 'waka', 'wali_kelas', 'koordinator_ekstra'])) {
            return true;
        }

        if ($this->isActiveHomeroomTeacher()) {
            return true;
        }

        return $this->isExtraCoordinator();
    }

    /**
     * Check if the user is a teacher / tenaga pendidik.
     */
    public function isTeacher(): bool
    {
        if (in_array($this->role, ['guru', 'wali_kelas'])) {
            return true;
        }

        if ($this->isActiveHomeroomTeacher()) {
            return true;
        }

        if (!$this->employee_id) {
            return false;
        }

        $emp = $this->employee;
        if (!$emp) {
            return false;
        }

        $empType = $emp->employeeType;
        if ($empType && $empType->code === 'teacher') {
            return true;
        }

        $pos = strtolower($emp->position ?? '');
        if (str_contains($pos, 'guru') || str_contains($pos, 'pendidik') || str_contains($pos, 'wali')) {
            return true;
        }

        return false;
    }

    /**
     * Get the employee profile associated with the user.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    protected static function booted()
    {
        static::updated(function ($user) {
            if ($user->employee_id && ($user->isDirty('name') || $user->isDirty('email'))) {
                $employee = \App\Models\Employee::find($user->employee_id);
                if ($employee) {
                    $rawName = $user->name;
                    $front = $employee->front_title;
                    $back = $employee->back_title;
                    \App\Models\Employee::sanitizeTitlesAndName($front, $rawName, $back);

                    $employee->updateQuietly([
                        'front_title' => $front ?: null,
                        'name' => $rawName,
                        'back_title' => $back ?: null,
                        'email' => $user->email,
                    ]);
                }
            }
        });
    }
}
