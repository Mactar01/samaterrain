import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';
import '../bloc/field_cubit.dart';
import '../../../auth/presentation/bloc/auth_cubit.dart';

class OwnerHomePage extends StatefulWidget {
  const OwnerHomePage({Key? key}) : super(key: key);

  @override
  State<OwnerHomePage> createState() => _OwnerHomePageState();
}

class _OwnerHomePageState extends State<OwnerHomePage> {
  @override
  void initState() {
    super.initState();
    context.read<FieldCubit>().fetchOwnerFields();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Espace Loueur - Mes Terrains'),
        backgroundColor: Colors.blueGrey,
      ),
      drawer: Drawer(
        child: ListView(
          padding: EdgeInsets.zero,
          children: [
            const DrawerHeader(
              decoration: BoxDecoration(color: Colors.blueGrey),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Icon(Icons.business, size: 60, color: Colors.white),
                  SizedBox(height: 10),
                  Text('Espace Loueur', style: TextStyle(color: Colors.white, fontSize: 24)),
                ],
              ),
            ),
            ListTile(
              leading: const Icon(Icons.logout, color: Colors.red),
              title: const Text('Se déconnecter', style: TextStyle(color: Colors.red)),
              onTap: () {
                Navigator.pop(context);
                context.read<AuthCubit>().logout();
                context.go('/login');
              },
            ),
          ],
        ),
      ),
      body: BlocBuilder<FieldCubit, FieldState>(
        builder: (context, state) {
          if (state is FieldLoading) {
            return const Center(child: CircularProgressIndicator());
          } else if (state is FieldError) {
            return Center(child: Text(state.message));
          } else if (state is FieldLoaded) {
            final fields = state.fields;
            if (fields.isEmpty) {
              return const Center(child: Text("Vous n'avez aucun terrain."));
            }
            return ListView.builder(
              itemCount: fields.length,
              itemBuilder: (context, index) {
                final field = fields[index];
                return Card(
                  margin: const EdgeInsets.all(8.0),
                  child: ListTile(
                    leading: const Icon(Icons.stadium, size: 40, color: Colors.blueGrey),
                    title: Text(field['name'] ?? 'Inconnu'),
                    subtitle: Text("${field['city']} • ${field['type']}"),
                    trailing: const Icon(Icons.arrow_forward_ios, size: 16),
                    onTap: () {
                      context.push('/field/${field['id']}'); // We can reuse the detail page, but maybe owner should have a slot management page? For now just detail is fine.
                    },
                  ),
                );
              },
            );
          }
          return const SizedBox();
        },
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: () {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('L\'ajout de terrain se fait sur l\'espace Web pour le moment.')),
          );
        },
        backgroundColor: Colors.blueGrey,
        child: const Icon(Icons.add),
      ),
    );
  }
}
