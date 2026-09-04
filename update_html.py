# -*- coding: utf-8 -*-
import re

with open("frontend/src/app/features/owner-dashboard/dashboard/dashboard.component.html", "r", encoding="utf-8") as f:
    content = f.read()

# 1. Update Sidebar Links
# Terrains link
content = re.sub(
    r'<a href="#" class="bg-slate-800 text-white group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg">([\s\S]*?)Mes Terrains\s*</a>',
    r'<a (click)="setTab(\'fields\')" [ngClass]="{\'bg-slate-800 text-white\': activeTab === \'fields\', \'text-slate-300 hover:bg-slate-800 hover:text-white\': activeTab !== \'fields\'}" class="cursor-pointer group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors">\1Mes Terrains\n          </a>',
    content
)

# Statistiques link
content = re.sub(
    r'<a href="#" class="text-slate-300 hover:bg-slate-800 hover:text-white group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors">([\s\S]*?)Statistiques\s*</a>',
    r'<a (click)="setTab(\'stats\')" [ngClass]="{\'bg-slate-800 text-white\': activeTab === \'stats\', \'text-slate-300 hover:bg-slate-800 hover:text-white\': activeTab !== \'stats\'}" class="cursor-pointer group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors">\1Statistiques\n            </a>',
    content
)

# Update SVG color for Terrains based on activeTab
content = re.sub(
    r'<svg class="text-green-400 mr-3',
    r'<svg [ngClass]="{\'text-green-400\': activeTab === \'fields\', \'text-slate-500 group-hover:text-slate-300\': activeTab !== \'fields\'}" class="mr-3',
    content
)

# Update SVG color for Statistiques based on activeTab
# (We need to be careful not to replace profile link)
content = content.replace(
    '''<svg class="text-slate-500 group-hover:text-slate-300 mr-3 flex-shrink-0 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
              Statistiques''',
    '''<svg [ngClass]="{'text-green-400': activeTab === 'stats', 'text-slate-500 group-hover:text-slate-300': activeTab !== 'stats'}" class="mr-3 flex-shrink-0 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
              Statistiques'''
)


# 2. Hide/Show Content blocks
# Wrap Stats block
content = content.replace(
    '<!-- Owner Dashboard Stats -->\n          <div *ngIf="ownerStats" class="mb-8">',
    '<!-- Owner Dashboard Stats -->\n          <div *ngIf="ownerStats && activeTab === \'stats\'" class="mb-8">'
)

# Wrap Terrains block
content = content.replace(
    '<!-- Terrains Grid -->\n          <h3 class="text-xl font-bold text-slate-800 mb-4">Mes Terrains</h3>\n          <div *ngIf="user?.role === \'owner\'" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">',
    '<!-- Terrains Grid -->\n          <div *ngIf="activeTab === \'fields\'">\n          <h3 class="text-xl font-bold text-slate-800 mb-4">Mes Terrains</h3>\n          <div *ngIf="user?.role === \'owner\'" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">'
)
# Close the div at the end of the fields block
content = content.replace(
    '<!-- ADMIN DASHBOARD (if admin) -->',
    '</div>\n          </div>\n          <!-- ADMIN DASHBOARD (if admin) -->'
)

with open("frontend/src/app/features/owner-dashboard/dashboard/dashboard.component.html", "w", encoding="utf-8") as f:
    f.write(content)
print("Updated HTML")
