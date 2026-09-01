import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';
import '../bloc/booking_cubit.dart';

class MyReservationsPage extends StatefulWidget {
  const MyReservationsPage({Key? key}) : super(key: key);

  @override
  State<MyReservationsPage> createState() => _MyReservationsPageState();
}

class _MyReservationsPageState extends State<MyReservationsPage> {
  @override
  void initState() {
    super.initState();
    context.read<BookingCubit>().fetchMyReservations();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Mes Réservations')),
      body: BlocBuilder<BookingCubit, BookingState>(
        buildWhen: (previous, current) => current is ReservationsLoading || current is ReservationsLoaded,
        builder: (context, state) {
          if (state is ReservationsLoading) {
            return const Center(child: CircularProgressIndicator());
          } else if (state is ReservationsLoaded) {
            final res = state.reservations;
            if (res.isEmpty) {
              return const Center(child: Text("Vous n'avez aucune réservation."));
            }

            return ListView.builder(
              itemCount: res.length,
              itemBuilder: (context, index) {
                final r = res[index];
                final field = r['field'];
                final timeSlot = r['time_slot'];
                final status = r['status'];
                
                Color statusColor = Colors.grey;
                String statusLabel = 'Inconnu';
                
                if (status == 'pending') {
                  statusColor = Colors.orange;
                  statusLabel = 'En attente de paiement';
                } else if (status == 'confirmed') {
                  statusColor = Colors.green;
                  statusLabel = 'Confirmée';
                } else if (status == 'cancelled') {
                  statusColor = Colors.red;
                  statusLabel = 'Annulée';
                }

                return Card(
                  margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                  child: ListTile(
                    title: Text(field['name'] ?? 'Terrain inconnu', style: const TextStyle(fontWeight: FontWeight.bold)),
                    subtitle: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('Date : ${timeSlot['date']}'),
                        Text('Heure : ${timeSlot['start_time']} - ${timeSlot['end_time']}'),
                        const SizedBox(height: 4),
                        Row(
                          children: [
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                              decoration: BoxDecoration(
                                color: statusColor.withOpacity(0.2),
                                borderRadius: BorderRadius.circular(4),
                              ),
                              child: Text(statusLabel, style: TextStyle(color: statusColor, fontWeight: FontWeight.bold, fontSize: 12)),
                            ),
                          ],
                        ),
                      ],
                    ),
                    isThreeLine: true,
                    trailing: status == 'pending'
                        ? ElevatedButton(
                            style: ElevatedButton.styleFrom(backgroundColor: Colors.blue),
                            onPressed: () {
                              context.push('/payment', extra: r);
                            },
                            child: const Text('Payer', style: TextStyle(color: Colors.white)),
                          )
                        : Text("${r['total_price']} FCFA", style: const TextStyle(fontWeight: FontWeight.bold)),
                  ),
                );
              },
            );
          }
          return const SizedBox();
        },
      ),
    );
  }
}
