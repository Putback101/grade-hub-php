<?php
/**
 * Grade Hub - Sample Data & Mock Data
 * 
 * This file contains sample data for initializing the application
 * with demo accounts and data.
 */

return [
    'users' => [
        [
            'name' => 'Dr. Maria Santos',
            'email' => 'maria.santos@university.edu',
            'password' => 'password123',
            'role' => 'faculty',
            'department' => 'Computer Science'
        ],
        [
            'name' => 'Prof. Juan Dela Cruz',
            'email' => 'juan.delacruz@university.edu',
            'password' => 'password123',
            'role' => 'faculty',
            'department' => 'Mathematics'
        ],
        [
            'name' => 'Ana Reyes',
            'email' => 'ana.reyes@university.edu',
            'password' => 'password123',
            'role' => 'registrar'
        ],
        [
            'name' => 'Carlos Garcia',
            'email' => 'carlos.garcia@student.edu',
            'password' => 'password123',
            'role' => 'student',
            'student_id' => '2024-00001'
        ],
        [
            'name' => 'Isabella Torres',
            'email' => 'isabella.torres@student.edu',
            'password' => 'password123',
            'role' => 'student',
            'student_id' => '2024-00002'
        ],
        [
            'name' => 'Admin User',
            'email' => 'admin@university.edu',
            'password' => 'password123',
            'role' => 'admin'
        ]
    ],
    'subjects' => [
        [
            'code' => 'CS101',
            'name' => 'Introduction to Programming',
            'units' => 3,
            'semester' => '1st',
            'academic_year' => '2024-2025'
        ],
        [
            'code' => 'CS102',
            'name' => 'Data Structures',
            'units' => 3,
            'semester' => '1st',
            'academic_year' => '2024-2025'
        ],
        [
            'code' => 'MATH101',
            'name' => 'Calculus I',
            'units' => 4,
            'semester' => '1st',
            'academic_year' => '2024-2025'
        ],
        [
            'code' => 'MATH102',
            'name' => 'Linear Algebra',
            'units' => 3,
            'semester' => '1st',
            'academic_year' => '2024-2025'
        ],
        [
            'code' => 'ENG101',
            'name' => 'Technical Writing',
            'units' => 3,
            'semester' => '1st',
            'academic_year' => '2024-2025'
        ]
    ],
    'admin_emails' => [
        'admin@university.edu'
    ]
];
?>
