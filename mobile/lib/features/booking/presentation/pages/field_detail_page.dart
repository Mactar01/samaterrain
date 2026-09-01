import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';
import '../bloc/booking_cubit.dart';

class FieldDetailPage extends StatefulWidget {
  final int fieldId;
  const FieldDetailPage({Key? key, required this.fieldId}) : super(key: key);

  @override
  State<FieldDetailPage> createState() => _FieldDetailPageState();
}

class _FieldDetailPageState extends State<FieldDetailPage> {
  DateTime selectedDate = DateTime.now();

  @override
  void initState() {
    super.initState();
    _fetchSlotsForDate(selectedDate);
  }

  void _fetchSlotsForDate(DateTime date) {
    final dateString = DateFormat('yyyy-MM-dd').format(date);
    context.read<BookingCubit>().fetchSlots(widget.fieldId, dateString);
  }

  Future<void> _selectDate(BuildContext context) async {
    final DateTime? picked = await showDatePicker(
      context: context,
      initialDate: selectedDate,
      firstDate: DateTime.now(),
      lastDate: DateTime.now().add(const Duration(days: 30)),
    );
    if (picked != null && picked != selectedDate) {
      setState(() {
        selectedDate = picked;
      });
      _fetchSlotsForDate(picked);
    }
  }

  void _bookSlot(int slotId) {
    context.read<BookingCubit>().bookSlot(slotId, 'Réservé depuis mobile');
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Détail & Réservation')),
      body: Column(
        children: [
          // Header / Info
          Container(
            padding: const EdgeInsets.all(16.0),
            color: Colors.green.shade50,
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  DateFormat('dd MMMM yyyy', 'fr_FR').format(selectedDate),
                  style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                ),
                ElevatedButton(
                  onPressed: () => _selectDate(context),
                  child: const Text('Changer'),
                )
              ],
            ),
          ),
          
          // Slots list
          Expanded(
            child: BlocConsumer<BookingCubit, BookingState>(
              listener: (context, state) {
                if (state is BookingSuccess) {
                  // Rediriger vers la page de paiement
                  context.push('/payment', extra: state.reservation);
                } else if (state is BookingFailure) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(content: Text(state.message), backgroundColor: Colors.red),
                  );
                }
              },
              buildWhen: (previous, current) {
                // We want to rebuild on SlotsLoading, SlotsLoaded, BookingLoading, and BookingFailure
                // Actually, let's just always rebuild to ensure the button state updates!
                return true;
              },
              builder: (context, state) {
                final cubit = context.read<BookingCubit>();
                if (state is SlotsLoading) {
                  return const Center(child: CircularProgressIndicator());
                } else if (state is SlotsLoaded || state is BookingLoading || state is BookingFailure || state is BookingSuccess) {
                  final slots = cubit.currentSlots;
                  
                  if (slots.isEmpty) {
                    return Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.event_busy, size: 64, color: Colors.grey.shade400),
                          const SizedBox(height: 16),
                          Text('Aucun créneau disponible ce jour.', style: TextStyle(color: Colors.grey.shade600, fontSize: 16)),
                        ],
                      )
                    );
                  }

                  return GridView.builder(
                    padding: const EdgeInsets.all(16),
                    gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                      crossAxisCount: 2,
                      childAspectRatio: 2.5,
                      crossAxisSpacing: 10,
                      mainAxisSpacing: 10,
                    ),
                    itemCount: slots.length,
                    itemBuilder: (context, index) {
                      final slot = slots[index];
                      final isAvailable = slot['status'] == 'available';
                      final isBookingThis = (state is BookingLoading && state.slotId == slot['id']);
                      final isBookingAnything = (state is BookingLoading);

                      // Format time purely like "14:00 - 15:00"
                      final timeStr = "${slot['start_time'].toString().substring(0,5)} - ${slot['end_time'].toString().substring(0,5)}";

                      return InkWell(
                        onTap: (isAvailable && !isBookingAnything) ? () => _bookSlot(slot['id']) : null,
                        borderRadius: BorderRadius.circular(12),
                        child: Container(
                          decoration: BoxDecoration(
                            color: isAvailable ? Colors.white : Colors.grey.shade200,
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(
                              color: isAvailable ? Colors.green.shade500 : Colors.grey.shade300,
                              width: 1.5,
                            ),
                            boxShadow: isAvailable ? [
                              BoxShadow(color: Colors.green.withOpacity(0.1), blurRadius: 4, offset: const Offset(0, 2))
                            ] : [],
                          ),
                          alignment: Alignment.center,
                          child: isBookingThis
                            ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2))
                            : Column(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  Text(timeStr, style: TextStyle(
                                    fontWeight: FontWeight.bold,
                                    color: isAvailable ? Colors.green.shade700 : Colors.grey.shade500,
                                    fontSize: 16,
                                  )),
                                  if (!isAvailable)
                                    Text('Indisponible', style: TextStyle(color: Colors.red.shade300, fontSize: 11)),
                                ],
                              ),
                        ),
                      );
                    },
                  );
                }
                return const SizedBox();
              },
            ),
          )
        ],
      ),
    );
  }
}
